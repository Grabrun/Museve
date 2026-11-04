/**
 * 前端依赖本地化（node tools/sync-vendor.mjs）
 *
 * 为什么要本地化：
 *   原来 alpine / pjax / dompurify / phosphor / tinymce 全部走 cdn.jsdelivr.net 与 unpkg，
 *   浮动版本 + 无 SRI。CDN 被劫持即全站 XSS；网络受限时全站 JS 直接不可用
 *   （实测本机环境下 cdn.jsdelivr.net 会被 reset）。
 *
 * 产物落在 resources/vendor/ 并纳入版本库，部署仍是"纯 rsync，无需构建"。
 * 升级依赖后重跑本脚本即可。
 */
import fs from 'node:fs';
import path from 'node:path';

const ROOT = path.resolve(process.cwd());
const NM = path.join(ROOT, 'node_modules');
const OUT = path.join(ROOT, 'resources', 'vendor');

/** @type {{from:string, to:string}[]} 单文件拷贝 */
const FILES = [
  { from: 'pjax/pjax.min.js', to: 'pjax/pjax.min.js' },
  { from: 'dompurify/dist/purify.min.js', to: 'dompurify/purify.min.js' },
  { from: '@phosphor-icons/web/src/regular/style.css', to: 'phosphor/style.css' },
  { from: '@phosphor-icons/web/src/regular/Phosphor.woff2', to: 'phosphor/Phosphor.woff2' },
  { from: '@phosphor-icons/web/src/regular/Phosphor.woff', to: 'phosphor/Phosphor.woff' },
];

/** @type {{from:string, to:string}[]} 整目录拷贝 */
const DIRS = [
  { from: 'tinymce/skins/ui/oxide', to: 'tinymce/skins/ui/oxide' },
  { from: 'tinymce/skins/ui/oxide-dark', to: 'tinymce/skins/ui/oxide-dark' },
  { from: 'tinymce/skins/content/default', to: 'tinymce/skins/content/default' },
];

/** TinyMCE 只拷贝编辑器实际用到的那一小撮，整包 8MB 没必要 */
const TINYMCE_FILES = [
  'tinymce.min.js',
  'themes/silver/theme.min.js',
  'models/dom/model.min.js',
  'icons/default/icons.min.js',
];
/** 与 admin/sections/article-edit.php 里 tinymce.init({ plugins }) 保持同步 */
const TINYMCE_PLUGINS = [
  'lists', 'link', 'image', 'table', 'code', 'codesample',
  'searchreplace', 'visualblocks', 'fullscreen',
];

let copied = 0;
const problems = [];

function ensureDir(file) {
  fs.mkdirSync(path.dirname(file), { recursive: true });
}

function copyFile(fromRel, toRel) {
  const src = path.join(NM, fromRel);
  const dst = path.join(OUT, toRel);
  if (!fs.existsSync(src)) {
    problems.push(`缺少源文件: node_modules/${fromRel}`);
    return;
  }
  ensureDir(dst);
  fs.copyFileSync(src, dst);
  copied++;
}

function copyDir(fromRel, toRel) {
  const src = path.join(NM, fromRel);
  const dst = path.join(OUT, toRel);
  if (!fs.existsSync(src)) {
    problems.push(`缺少源目录: node_modules/${fromRel}`);
    return;
  }
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const childFrom = path.join(fromRel, entry.name).replace(/\\/g, '/');
    const childTo = path.join(toRel, entry.name).replace(/\\/g, '/');
    if (entry.isDirectory()) {
      copyDir(childFrom, childTo);
    } else {
      copyFile(childFrom, childTo);
    }
  }
}

fs.rmSync(OUT, { recursive: true, force: true });

for (const f of FILES) copyFile(f.from, f.to);
for (const d of DIRS) copyDir(d.from, d.to);
for (const f of TINYMCE_FILES) copyFile(`tinymce/${f}`, `tinymce/${f}`);
for (const p of TINYMCE_PLUGINS) copyFile(`tinymce/plugins/${p}/plugin.min.js`, `tinymce/plugins/${p}/plugin.min.js`);

// TinyMCE 的 icons 包还带 index.js，运行时不会用到，跳过

if (problems.length) {
  console.error('✗ 依赖同步失败：');
  for (const p of problems) console.error('  - ' + p);
  process.exit(1);
}

const size = (function du(dir) {
  let total = 0;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    total += entry.isDirectory() ? du(p) : fs.statSync(p).size;
  }
  return total;
})(OUT);

console.log(`✓ 已同步 ${copied} 个文件 → resources/vendor/（${(size / 1024 / 1024).toFixed(2)} MB）`);
