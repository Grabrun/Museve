/**
 * Tailwind 类名覆盖检查（node tools/check-classes.mjs）
 *
 * 从 Play CDN 换成构建期预编译后，最大的风险是"某个类没被扫到"，
 * 表现为线上样式局部丢失，而且不看页面根本发现不了。
 *
 * 做法：把模板里 class / :class 的属性值按空白切词（保留 md: / lg: 这类
 * 变体前缀与 [任意值] 方括号），逐个比对构建产物，只报"看起来是 Tailwind
 * 语法、但产物里没有"的项。
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(process.cwd());
const CSS = path.join(ROOT, 'resources/css/tailwind.css');

if (!fs.existsSync(CSS)) {
  console.error('✗ 未找到 resources/css/tailwind.css，先执行 npm run build:css');
  process.exit(1);
}

const css = fs.readFileSync(CSS, 'utf8');

/** 项目自定义类（style.css / admin.css）里的类名，这些不需要 Tailwind 生成 */
const customCss = ['resources/css/style.css', 'resources/css/admin.css']
  .map((p) => path.join(ROOT, p))
  .filter((p) => fs.existsSync(p))
  .map((p) => fs.readFileSync(p, 'utf8'))
  .join('\n');

/**
 * CSS 里的字符转义还原
 *
 * Tailwind 会把 `[ ] : / . , ( )` 转义成 `\[ \] \: \/ \. \( \)`，
 * 逗号还会写成十六进制 `\2c `。比对前统一还原，避免误报。
 */
function unescapeCss(text) {
  return text
    .replace(/\\([0-9a-fA-F]{1,6})\s?/g, (_, hex) => {
      const code = parseInt(hex, 16);
      return Number.isFinite(code) && code > 0 ? String.fromCodePoint(code) : '';
    })
    .replace(/\\(.)/g, '$1');
}

const cssFlat = unescapeCss(css);
const customFlat = unescapeCss(customCss);

function escapeRegex(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/** 某个类是否被 Tailwind 生成，或由项目自有样式定义 */
function classExists(token) {
  const pattern = new RegExp('\\.' + escapeRegex(token) + '(?=[{\\s,:>~+\\[])');
  return pattern.test(cssFlat) || pattern.test(customFlat);
}

const THEME_COLORS = [
  'museve-bg', 'museve-rose', 'museve-rose-deep', 'museve-haze', 'museve-blue',
  'museve-night', 'museve-gray', 'museve-green', 'museve-orange', 'museve-red', 'museve-ash',
];

const TW_PREFIX = new RegExp(
  '^(' + [
    'text', 'bg', 'border', 'divide', 'ring', 'outline', 'shadow', 'opacity',
    'p', 'px', 'py', 'pt', 'pr', 'pb', 'pl', 'm', 'mx', 'my', 'mt', 'mr', 'mb', 'ml',
    'w', 'h', 'min-w', 'min-h', 'max-w', 'max-h', 'size',
    'flex', 'grid', 'gap', 'space', 'items', 'justify', 'content', 'self', 'place',
    'col', 'row', 'order', 'basis', 'grow', 'shrink',
    'font', 'tracking', 'leading', 'list', 'whitespace', 'break', 'truncate', 'line-clamp',
    'rounded', 'aspect', 'object', 'overflow', 'overscroll',
    'relative', 'absolute', 'fixed', 'sticky', 'inset', 'top', 'right', 'bottom', 'left',
    'z', 'float', 'clear', 'visible', 'invisible', 'hidden', 'block', 'inline', 'table', 'contents',
    'transition', 'duration', 'delay', 'ease', 'animate', 'transform', 'translate', 'rotate', 'scale', 'origin',
    'cursor', 'select', 'resize', 'appearance', 'pointer-events', 'user-select',
    'backdrop', 'blur', 'brightness', 'contrast', 'grayscale', 'invert', 'saturate', 'sepia',
    'sr', 'not-sr', 'prose',
  ].join('|') + ')(-|$)'
);

function walk(dir, ext) {
  if (!fs.existsSync(dir)) return [];
  const out = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (entry.name === 'vendor' || entry.name === 'node_modules') continue;
      out.push(...walk(p, ext));
    } else if (entry.name.endsWith(ext)) {
      out.push(p);
    }
  }
  return out;
}

const FILES = [
  path.join(ROOT, 'index.php'),
  path.join(ROOT, '404.html'),
  ...walk(path.join(ROOT, 'includes'), '.php'),
  ...walk(path.join(ROOT, 'sections'), '.php'),
  ...walk(path.join(ROOT, 'admin'), '.php'),
  ...walk(path.join(ROOT, 'resources/js'), '.js'),
].filter((f) => fs.existsSync(f));

function isTailwindCandidate(token) {
  if (THEME_COLORS.some((c) => token.includes(c))) return true;
  if (token.includes('[') || token.includes(':')) return true;
  // 变体前缀（md:flex）去掉后再判一次
  const bare = token.replace(/^[a-z0-9-]+:/, '');
  return TW_PREFIX.test(bare);
}

/** CSS 里某个类是否真的存在（Tailwind 会转义 : [ ] . / % ( ) 等） */
function cssHasClass(token) {
  return classExists(token);
}

const missing = new Map();
let checked = 0;

for (const file of FILES) {
  const src = fs.readFileSync(file, 'utf8');
  const rel = path.relative(ROOT, file).replace(/\\/g, '/');

  const attrRe = /:?class="([^"]*)"/g;
  let m;
  while ((m = attrRe.exec(src)) !== null) {
    for (const rawToken of m[1].split(/\s+/)) {
      // 去掉两侧的引号/括号/问号等表达式残留，但保留 [ ] : / 这些 Tailwind 语义字符
      const t = rawToken.replace(/^[?"'`(){},;]+/, '').replace(/[?"'`(){},;]+$/, '');
      if (!t || t.length > 80) continue;
      if (!/^-?[a-zA-Z][-a-zA-Z0-9._:/[\]()%#,+*~<>=&!]*$/.test(t)) continue;
      if (!isTailwindCandidate(t)) continue;

      checked++;
      if (!cssHasClass(t)) {
        if (!missing.has(t)) missing.set(t, new Set());
        missing.get(t).add(rel);
      }
    }
  }
}

if (missing.size === 0) {
  console.log(`✓ 类名覆盖检查通过（比对 ${checked} 次引用，无缺失）`);
  process.exit(0);
}

console.error(`✗ ${missing.size} 个 Tailwind 类未出现在构建产物中：`);
for (const [token, files] of [...missing.entries()].sort()) {
  console.error(`  - ${token}    (${[...files].slice(0, 3).join(', ')})`);
}
process.exit(1);
