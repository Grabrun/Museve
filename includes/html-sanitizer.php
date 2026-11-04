<?php
/**
 * 暮想 Museve — 富文本服务端净化
 *
 * 为什么需要它：
 *   PHP 的 strip_tags($html, $allowedTags) 只过滤"标签名"，**完全不动属性**。
 *   所以 `<img src=x onerror="fetch(...)">` 里的 onerror、`<a href="javascript:">`
 *   都会被原样存进 articles.content，再由 sections/read.php 原样输出给访客。
 *   浏览器里的 DOMPurify 不是安全边界——攻击者直接对 API 发 JSON 就绕过了。
 *
 * 因此净化必须发生在服务端，且必须是白名单：
 *   1. 标签白名单：不在名单里的一律拆壳（保留文字内容），危险容器整块丢弃；
 *   2. 属性白名单：一切 on* 事件属性无条件丢弃；
 *   3. URL 协议白名单：http/https/mailto/tel/相对路径；data: 仅放行图片；
 *   4. style 走属性值白名单，阻断 url()/expression 一类外发与穿透。
 *
 * 实现基于 DOMDocument（libxml），不依赖 composer，符合项目"免构建部署"约束。
 *
 * @package Museve
 * @since   1.1.0
 */

/** 允许保留的标签（与编辑器工具栏能力对齐） */
const MUSEVE_ALLOWED_TAGS = [
    'p', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'strong', 'b', 'em', 'i', 'u', 's', 'del', 'ins', 'mark', 'sub', 'sup',
    'a', 'img', 'blockquote', 'q', 'cite',
    'ul', 'ol', 'li', 'dl', 'dt', 'dd',
    'pre', 'code', 'kbd', 'hr', 'div', 'span',
    'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
    'figure', 'figcaption',
];

/** 允许保留的属性（style 另行做值级过滤） */
const MUSEVE_ALLOWED_ATTRS = [
    'href', 'src', 'alt', 'title', 'class', 'style',
    'target', 'rel', 'loading', 'width', 'height',
    'colspan', 'rowspan', 'scope', 'start', 'reversed', 'datetime',
];

/** 连内容一起丢弃的标签（拆壳会留下可执行/可外发的残留） */
const MUSEVE_DROP_TAGS = [
    'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed',
    'applet', 'svg', 'math', 'form', 'input', 'button', 'select', 'textarea',
    'option', 'optgroup', 'link', 'meta', 'base', 'template', 'noscript',
    'canvas', 'audio', 'video', 'source', 'track', 'map', 'area', 'param',
];

/** style 属性里允许出现的 CSS 属性 */
const MUSEVE_ALLOWED_CSS_PROPS = [
    'text-align', 'float', 'clear',
    'color', 'background-color',
    'font-size', 'font-weight', 'font-style', 'font-family',
    'text-decoration', 'text-indent', 'letter-spacing', 'line-height',
    'white-space', 'vertical-align',
    'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
    'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
    'width', 'max-width', 'height', 'max-height',
    'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
    'border-radius', 'border-collapse', 'border-color', 'border-width', 'border-style',
];

/**
 * 净化富文本
 *
 * @param  string $html 原始 HTML
 * @return string 安全的 HTML
 */
function sanitizeRichHtml(string $html): string {
    if ($html === '') {
        return '';
    }

    // 防御性上限：正常文章远小于 2MB，超限直接按纯文本转义，避免解析器被打爆
    if (strlen($html) > 2 * 1024 * 1024) {
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $prevErrorMode = libxml_use_internal_errors(true);

    // <?xml encoding> 提示 libxml 以 UTF-8 解析，否则中文会被当 Latin-1 处理成乱码
    $loaded = $doc->loadHTML(
        '<?xml encoding="UTF-8"?>' . $html,
        LIBXML_HTML_NODEFDTD | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prevErrorMode);

    if ($loaded === false) {
        // 解析失败时退化为纯文本，宁可丢格式也不能放行未净化内容
        return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    $body = $doc->getElementsByTagName('body')->item(0);

    // libxml 会把 <base>/<meta>/<link> 这类 head 专属标签提升到 <head>，
    // 此时文档里可能压根没有 <body>。不能因此退化成纯文本（那样攻击者
    // 只要在正文开头塞一个 <base> 就能让整篇文章变成转义字符串）。
    // 处理方式：显式摘掉 <head> 整块，再从 <html> 上取内容。
    if (!$body) {
        $head = $doc->getElementsByTagName('head')->item(0);
        if ($head && $head->parentNode) {
            $head->parentNode->removeChild($head);
        }
        $body = $doc->documentElement;
    }

    if (!$body) {
        return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    museveSanitizeChildren($body);

    $out = '';
    foreach ($body->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }

    return trim($out);
}

/**
 * 递归净化某个节点的全部子节点
 *
 * @param  DOMNode $node 容器节点
 * @return void
 */
function museveSanitizeChildren(DOMNode $node): void {
    $snapshot = [];
    foreach ($node->childNodes as $child) {
        $snapshot[] = $child;
    }

    foreach ($snapshot as $child) {
        if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
            // 注释里可能藏条件注释 payload；PI 是解析器残留，一并清掉
            $node->removeChild($child);
            continue;
        }

        if (!($child instanceof DOMElement)) {
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (in_array($tag, MUSEVE_DROP_TAGS, true)) {
            $node->removeChild($child);
            continue;
        }

        museveFilterAttributes($child, $tag);
        museveSanitizeChildren($child);

        if (!in_array($tag, MUSEVE_ALLOWED_TAGS, true)) {
            // 不在白名单：拆壳保留内容（例如 <font>、<center> 这类遗留标签）
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
        }
    }
}

/**
 * 过滤元素属性
 *
 * @param  DOMElement $el  元素
 * @param  string     $tag 小写标签名
 * @return void
 */
function museveFilterAttributes(DOMElement $el, string $tag): void {
    $names = [];
    foreach ($el->attributes as $attr) {
        $names[] = $attr->nodeName;
    }

    foreach ($names as $name) {
        $lower = strtolower($name);

        // on* 事件属性一律丢弃（XSS 主入口）
        if (str_starts_with($lower, 'on')) {
            $el->removeAttribute($name);
            continue;
        }

        if (!in_array($lower, MUSEVE_ALLOWED_ATTRS, true)) {
            $el->removeAttribute($name);
            continue;
        }

        $value = $el->getAttribute($name);

        if (in_array($lower, ['href', 'src'], true)) {
            if (!museveIsSafeUrl($value, $lower === 'src')) {
                $el->removeAttribute($name);
            }
            continue;
        }

        if ($lower === 'style') {
            $safe = museveFilterStyle($value);
            if ($safe === '') {
                $el->removeAttribute($name);
            } else {
                $el->setAttribute($name, $safe);
            }
            continue;
        }

        if ($lower === 'target') {
            if (!in_array($value, ['_blank', '_self'], true)) {
                $el->removeAttribute($name);
            }
            continue;
        }
    }

    // target=_blank 必须补 noopener/noreferrer，防 window.opener 反向控制
    if ($el->getAttribute('target') === '_blank') {
        $rel = preg_split('/\s+/', trim($el->getAttribute('rel'))) ?: [];
        $rel = array_filter($rel);
        foreach (['noopener', 'noreferrer'] as $need) {
            if (!in_array($need, $rel, true)) {
                $rel[] = $need;
            }
        }
        $el->setAttribute('rel', implode(' ', $rel));
    }

    // 图片统一懒加载，顺带规避超大图撑爆首屏
    if ($tag === 'img' && !$el->hasAttribute('loading')) {
        $el->setAttribute('loading', 'lazy');
    }
}

/**
 * 判断 URL 是否安全
 *
 * @param  string $url   原始值
 * @param  bool   $isSrc 是否来自 src（决定 data: 是否放行）
 * @return bool
 */
function museveIsSafeUrl(string $url, bool $isSrc = false): bool {
    $url = trim($url);
    if ($url === '') {
        return false;
    }

    // 去掉空白与控制字符：`java\tscript:` / `java&#10;script:` 是经典绕过
    $clean = preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '';
    if ($clean === '') {
        return false;
    }

    $lower = strtolower($clean);

    if (str_starts_with($lower, 'data:')) {
        if (!$isSrc) {
            return false;
        }
        // 仅允许常见图片格式的 base64 data URI
        return preg_match('#^data:image/(png|jpe?g|gif|webp|avif);base64,[A-Za-z0-9+/=\s]+$#i', $clean) === 1;
    }

    // 协议相对地址 //example.com/x
    if (str_starts_with($clean, '//')) {
        return true;
    }

    if (preg_match('#^(?:https?|mailto|tel):#i', $clean)) {
        return true;
    }

    // 出现协议说明但不是上面放行的，一律拒绝（javascript:、vbscript:、file: 等）
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $clean)) {
        return false;
    }

    // 相对路径、锚点、查询串
    return true;
}

/**
 * 过滤 style 属性值
 *
 * 只保留白名单属性；值里出现 url()/expression/@import 一类一律整条丢弃。
 *
 * @param  string $style 原始 style
 * @return string 安全的 style（可能为空串）
 */
function museveFilterStyle(string $style): string {
    $style = trim($style);
    if ($style === '' || strlen($style) > 1000) {
        return '';
    }

    $kept = [];
    foreach (explode(';', $style) as $declaration) {
        $declaration = trim($declaration);
        if ($declaration === '' || !str_contains($declaration, ':')) {
            continue;
        }

        [$prop, $value] = explode(':', $declaration, 2);
        $prop  = strtolower(trim($prop));
        $value = trim($value);

        if (!in_array($prop, MUSEVE_ALLOWED_CSS_PROPS, true)) {
            continue;
        }
        if ($value === '') {
            continue;
        }

        // 危险构造：外发、脚本、注释截断、转义
        if (preg_match('/url\s*\(|expression\s*\(|javascript:|@import|[\\\\<>{}]/i', $value)) {
            continue;
        }

        $kept[] = $prop . ': ' . $value;
    }

    return implode('; ', $kept);
}
