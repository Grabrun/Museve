<?php
/**
 * 富文本净化器回归测试
 *
 * 用法：tools/php/php.exe tools/test-sanitizer.php
 * 仅依赖 includes/html-sanitizer.php，不连数据库。
 */

require __DIR__ . '/../includes/html-sanitizer.php';

/** @var array<int, array{0:string,1:string,2:string[]}> [名称, 输入, 输出中必须不存在的片段] */
$cases = [
    ['事件属性 onerror', '<img src="/uploads/a.png" onerror="alert(1)">', ['onerror', 'alert(1)']],
    ['事件属性 onclick', '<div onclick="steal()">hi</div>', ['onclick', 'steal()']],
    ['javascript: 协议', '<a href="javascript:alert(1)">x</a>', ['javascript:', 'alert(1)']],
    ['大小写混淆', '<a href="JaVaScRiPt:alert(1)">x</a>', ['alert(1)']],
    ['制表符绕过', "<a href=\"java\tscript:alert(1)\">x</a>", ['alert(1)']],
    ['HTML 实体绕过', '<a href="java&#9;script:alert(1)">x</a>', ['alert(1)']],
    ['vbscript:', '<a href="vbscript:msgbox(1)">x</a>', ['vbscript:', 'msgbox']],
    ['data:text/html', '<img src="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">', ['data:text/html', 'PHNjcmlwdD']],
    ['script 整块丢弃', '<p>a</p><script>alert(1)</script>', ['<script', 'alert(1)']],
    ['style 标签丢弃', '<style>body{display:none}</style><p>x</p>', ['<style', 'display:none']],
    ['svg 整块丢弃', '<svg><script>alert(1)</script></svg>', ['<svg', 'alert(1)']],
    ['iframe 丢弃', '<iframe src="//evil.example/x"></iframe>', ['<iframe', 'evil.example']],
    ['form/input 丢弃', '<form action="//evil"><input name="x"></form>', ['<form', '<input']],
    ['base 丢弃', '<base href="//evil.example/">', ['<base', 'evil.example']],
    ['style 里 url()', '<p style="background:url(javascript:alert(1))">x</p>', ['url(', 'javascript:']],
    ['style 里 expression', '<p style="width:expression(alert(1))">x</p>', ['expression']],
    ['style 全屏覆盖', '<p style="position:fixed;top:0;left:0;width:100vw;height:100vh">x</p>', ['position', 'top:', 'left:']],
    ['注释剥离', '<p>a</p><!--[if IE]><script>alert(1)</script><![endif]-->', ['<script', 'alert(1)']],
    ['math 命名空间', '<math><mtext><script>alert(1)</script></mtext></math>', ['<math', 'alert(1)']],
];

/** @var array<int, array{0:string,1:string,2:string[]}> [名称, 输入, 输出中必须保留的片段] */
$keepCases = [
    ['普通段落', '<p>这是一段中文</p>', ['<p>这是一段中文</p>']],
    ['图片保留', '<img src="/uploads/a.png" alt="说明">', ['src="/uploads/a.png"', 'alt="说明"']],
    ['图片 base64', '<img src="data:image/png;base64,iVBORw0KGgo=">', ['data:image/png;base64']],
    ['https 链接', '<a href="https://example.com">链接</a>', ['https://example.com']],
    ['相对链接', '<a href="/read/12">内链</a>', ['/read/12']],
    ['锚点链接', '<a href="#top">顶部</a>', ['#top']],
    ['mailto', '<a href="mailto:a@b.com">写信</a>', ['mailto:a@b.com']],
    ['表格结构', '<table><tr><td colspan="2">x</td></tr></table>', ['<table>', 'colspan="2"']],
    ['列表', '<ul><li>一</li><li>二</li></ul>', ['<ul>', '<li>一</li>']],
    ['保留对齐样式', '<p style="text-align: center">居中</p>', ['text-align: center']],
    ['代码块', '<pre><code class="language-php">echo 1;</code></pre>', ['<pre>', 'language-php']],
];

$fail = 0;
$total = 0;

function report(string $name, bool $ok, string $detail): void {
    global $fail, $total;
    $total++;
    if ($ok) {
        echo "  ✓ {$name}\n";
    } else {
        $fail++;
        echo "  ✗ {$name}\n      {$detail}\n";
    }
}

echo "== 必须被拦截 ==\n";
foreach ($cases as [$name, $input, $forbidden]) {
    $out = sanitizeRichHtml($input);
    $hits = [];
    foreach ($forbidden as $needle) {
        if (stripos($out, $needle) !== false) {
            $hits[] = $needle;
        }
    }
    report($name, $hits === [], '仍残留: ' . implode(', ', $hits) . ' → ' . $out);
}

echo "\n== 必须被保留 ==\n";
foreach ($keepCases as [$name, $input, $expect]) {
    $out = sanitizeRichHtml($input);
    $missing = [];
    foreach ($expect as $needle) {
        if (stripos($out, $needle) === false) {
            $missing[] = $needle;
        }
    }
    report($name, $missing === [], '丢失: ' . implode(', ', $missing) . ' → ' . $out);
}

echo "\n== 结构性断言 ==\n";
$out = sanitizeRichHtml('<a href="https://x.com" target="_blank">a</a>');
report('target=_blank 自动补 rel', str_contains($out, 'noopener') && str_contains($out, 'noreferrer'), $out);

$out = sanitizeRichHtml('<div><font color="red">旧标签</font></div>');
report('未知标签拆壳保留文字', str_contains($out, '旧标签') && !str_contains($out, '<font'), $out);

$out = sanitizeRichHtml('<img src="/a.png">');
report('图片自动 lazy', str_contains($out, 'loading="lazy"'), $out);

$out = sanitizeRichHtml('');
report('空串安全', $out === '', '返回: ' . $out);

$out = sanitizeRichHtml('<p>a &amp; b &lt; c</p>');
report('实体不被二次解码', str_contains($out, '&amp;'), $out);

echo "\n";
if ($fail === 0) {
    echo "✓ 全部通过（{$total} 项断言）\n";
    exit(0);
}
echo "✗ {$fail} / {$total} 项失败\n";
exit(1);
