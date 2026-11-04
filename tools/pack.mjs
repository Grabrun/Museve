#!/usr/bin/env node
/**
 * Museve 纯净部署包构建器
 *
 * 为什么不用 zip/rsync：目标机器不一定有，而且"纯净"需要可审计的取舍规则。
 * 这里用白名单起步（只允许已知的运行期目录），再叠一层黑名单，任何新增的
 * 开发产物默认不会被打进去。
 *
 * 用法：
 *   node tools/pack.mjs                 # 输出 dist/museve-<时间戳>-<sha>.zip
 *   node tools/pack.mjs --name foo.zip  # 指定文件名
 *
 * 依赖：Node 内置模块（zlib 做 deflate，自实现 crc32 与 ZIP 结构）。
 */

import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DIST = path.join(ROOT, 'dist');

/* ------------------------------------------------------------------ *
 * 1. 取舍规则
 * ------------------------------------------------------------------ */

/** 顶层白名单：只有这些会进入部署包 */
const ALLOW_TOP = [
  'index.php',
  '404.html',
  'robots.txt',
  'sitemap.php',
  'admin',
  'api',
  'includes',
  'sections',
  'resources',
  'tools',
  'uploads',
];

/** 整目录丢弃（相对路径精确匹配，避免误伤同名目录） */
const DENY_DIRS = new Set([
  'tools/php',          // 本地便携 PHP 运行时
]);

/** 后缀丢弃 */
const DENY_EXT = new Set(['.md', '.log', '.mjs', '.map']);

/** 精确丢弃 */
const DENY_FILES = new Set([
  'includes/config.php',                 // 含数据库口令，绝不入包
  'tools/test-sanitizer.php',            // 开发期断言脚本
  'resources/css/tailwind.src.css',      // 样式源码，产物是 tailwind.css
]);

/** tools/ 下只保留这个（历史数据一次性净化，服务器上要能执行） */
const TOOLS_ALLOW = new Set(['tools/resanitize-articles.php']);

function shouldSkip(rel) {
  const base = path.posix.basename(rel);
  if (DENY_DIRS.has(rel)) return true;
  if (DENY_FILES.has(rel)) return true;
  if (DENY_EXT.has(path.extname(base))) return true;
  if (base === '.DS_Store' || base === 'Thumbs.db' || base === '.gitignore') return true;
  if (rel.startsWith('tools/') && !TOOLS_ALLOW.has(rel)) return true;
  return false;
}

/* ------------------------------------------------------------------ *
 * 2. 收集文件
 * ------------------------------------------------------------------ */

const files = [];   // { rel, abs }
const dirs = [];    // 需要显式建的空目录

function walk(absDir, relDir) {
  const entries = fs
    .readdirSync(absDir, { withFileTypes: true })
    .sort((a, b) => a.name.localeCompare(b.name));

  for (const e of entries) {
    const rel = relDir ? `${relDir}/${e.name}` : e.name;
    if (shouldSkip(rel)) continue;

    const abs = path.join(absDir, e.name);
    if (e.isDirectory()) {
      dirs.push(rel);
      walk(abs, rel);
    } else if (e.isFile()) {
      files.push({ rel, abs });
    } else if (e.isSymbolicLink()) {
      warn(`跳过符号链接：${rel}`);
    }
  }
}

const warnings = [];
const warn = (msg) => warnings.push(msg);

for (const top of ALLOW_TOP) {
  const abs = path.join(ROOT, top);
  if (!fs.existsSync(abs)) {
    warn(`白名单项不存在，已忽略：${top}`);
    continue;
  }
  const st = fs.statSync(abs);
  if (st.isDirectory()) {
    dirs.push(top);
    walk(abs, top);
  } else {
    files.push({ rel: top, abs });
  }
}

/* ------------------------------------------------------------------ *
 * 3. 预检：任何一项不通过都不出包
 * ------------------------------------------------------------------ */

const errors = [];

if (!fs.existsSync(path.join(ROOT, 'resources/css/tailwind.css'))) {
  errors.push('resources/css/tailwind.css 不存在，请先执行 npm run build');
}
if (files.length === 0) errors.push('没有收集到任何文件');

const relSet = new Set(files.map((f) => f.rel));
for (const forbidden of ['includes/config.php', 'package.json', 'nginx.conf', 'README.md']) {
  if (relSet.has(forbidden)) errors.push(`禁止入包的文件出现在结果中：${forbidden}`);
}
for (const rel of relSet) {
  if (rel.startsWith('.git/') || rel.startsWith('node_modules/') || rel.startsWith('docs/')) {
    errors.push(`禁止入包的目录出现在结果中：${rel}`);
  }
}

if (errors.length) {
  console.error('✗ 预检失败：');
  for (const e of errors) console.error(`   - ${e}`);
  process.exit(1);
}

/* ------------------------------------------------------------------ *
 * 4. ZIP 写入（store / deflate 二选一，逐文件比较）
 * ------------------------------------------------------------------ */

const crcTable = (() => {
  const t = new Int32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    t[n] = c;
  }
  return t;
})();

function crc32(buf) {
  let crc = 0xffffffff;
  for (let i = 0; i < buf.length; i++) {
    crc = crcTable[(crc ^ buf[i]) & 0xff] ^ (crc >>> 8);
  }
  return (crc ^ 0xffffffff) >>> 0;
}

function dosStamp(mtime) {
  const d = mtime instanceof Date && !Number.isNaN(mtime) ? mtime : new Date();
  const year = Math.max(1980, d.getFullYear());
  return {
    time: ((d.getHours() << 11) | (d.getMinutes() << 5) | (d.getSeconds() >> 1)) & 0xffff,
    date: (((year - 1980) << 9) | ((d.getMonth() + 1) << 5) | d.getDate()) & 0xffff,
  };
}

const chunks = [];
const central = [];
let offset = 0;

function addEntry(name, data, mtime, isDir) {
  const nameBuf = Buffer.from(name, 'utf8');
  const { time, date } = dosStamp(mtime);

  let method = 0;
  let payload = data;
  if (!isDir && data.length > 0) {
    const deflated = zlib.deflateRawSync(data, { level: 9 });
    if (deflated.length < data.length) {
      method = 8;
      payload = deflated;
    }
  }

  const crc = isDir ? 0 : crc32(data);
  const usize = isDir ? 0 : data.length;

  const local = Buffer.alloc(30);
  local.writeUInt32LE(0x04034b50, 0);
  local.writeUInt16LE(20, 4);        // version needed
  local.writeUInt16LE(0x0800, 6);    // UTF-8 文件名
  local.writeUInt16LE(method, 8);
  local.writeUInt16LE(time, 10);
  local.writeUInt16LE(date, 12);
  local.writeUInt32LE(crc, 14);
  local.writeUInt32LE(payload.length, 18);
  local.writeUInt32LE(usize, 22);
  local.writeUInt16LE(nameBuf.length, 26);
  local.writeUInt16LE(0, 28);

  chunks.push(local, nameBuf, payload);

  const cd = Buffer.alloc(46);
  cd.writeUInt32LE(0x02014b50, 0);
  cd.writeUInt16LE(0x031e, 4);       // made by: unix, v3
  cd.writeUInt16LE(20, 6);
  cd.writeUInt16LE(0x0800, 8);
  cd.writeUInt16LE(method, 10);
  cd.writeUInt16LE(time, 12);
  cd.writeUInt16LE(date, 14);
  cd.writeUInt32LE(crc, 16);
  cd.writeUInt32LE(payload.length, 20);
  cd.writeUInt32LE(usize, 24);
  cd.writeUInt16LE(nameBuf.length, 28);
  cd.writeUInt16LE(0, 30);
  cd.writeUInt16LE(0, 32);
  cd.writeUInt16LE(0, 34);
  cd.writeUInt16LE(0, 36);
  cd.writeUInt32LE(isDir ? ((0o40755 << 16) | 0x10) >>> 0 : (0o644 << 16) >>> 0, 38);
  cd.writeUInt32LE(offset, 42);

  central.push(cd, nameBuf);
  offset += local.length + nameBuf.length + payload.length;
}

// 目录条目（保留 uploads/memories 这类空目录结构）
for (const rel of dirs) {
  const abs = path.join(ROOT, rel);
  let mtime = new Date();
  try { mtime = fs.statSync(abs).mtime; } catch { /* 忽略 */ }
  addEntry(`${rel}/`, Buffer.alloc(0), mtime, true);
}

let rawBytes = 0;
let packedBytes = 0;
for (const f of files) {
  const data = fs.readFileSync(f.abs);
  const st = fs.statSync(f.abs);
  rawBytes += data.length;
  addEntry(f.rel, data, st.mtime, false);
  packedBytes += data.length;
}

const cdBuf = Buffer.concat(central);
const eocd = Buffer.alloc(22);
eocd.writeUInt32LE(0x06054b50, 0);
eocd.writeUInt16LE(0, 4);
eocd.writeUInt16LE(0, 6);
eocd.writeUInt16LE(dirs.length + files.length, 8);
eocd.writeUInt16LE(dirs.length + files.length, 10);
eocd.writeUInt32LE(cdBuf.length, 12);
eocd.writeUInt32LE(offset, 16);
eocd.writeUInt16LE(0, 20);

const zip = Buffer.concat([...chunks, cdBuf, eocd]);

/* ------------------------------------------------------------------ *
 * 5. 落盘
 * ------------------------------------------------------------------ */

const argName = process.argv.indexOf('--name');
const sha = (() => {
  try {
    return execSync('git rev-parse --short HEAD', { cwd: ROOT, encoding: 'utf8' }).trim();
  } catch {
    return 'nogit';
  }
})();
// 用本地时间命名：UTC 会让包名看起来早一天（GMT+8 凌晨发布时尤其明显）
const now = new Date();
const p2 = (n) => String(n).padStart(2, '0');
const stamp =
  `${now.getFullYear()}${p2(now.getMonth() + 1)}${p2(now.getDate())}` +
  `-${p2(now.getHours())}${p2(now.getMinutes())}${p2(now.getSeconds())}`;

const outName = argName > -1 ? process.argv[argName + 1] : `museve-${stamp}-${sha}.zip`;

fs.mkdirSync(DIST, { recursive: true });
const outPath = path.join(DIST, outName);
fs.writeFileSync(outPath, zip);

/* ------------------------------------------------------------------ *
 * 6. 报告
 * ------------------------------------------------------------------ */

const kb = (n) => `${(n / 1024).toFixed(1)} KB`;
console.log(`✓ 已生成 ${path.relative(ROOT, outPath)}`);
console.log(`  文件 ${files.length} 个 / 目录 ${dirs.length} 个`);
console.log(`  原始 ${kb(rawBytes)} → 压缩后 ${kb(zip.length)}（压掉 ${(100 - (zip.length / rawBytes) * 100).toFixed(1)}%）`);

if (warnings.length) {
  console.log('\n⚠ 提示：');
  for (const w of warnings) console.log(`   - ${w}`);
}

console.log('\n按顶层目录分布：');
const tally = new Map();
for (const f of files) {
  const top = f.rel.includes('/') ? f.rel.split('/')[0] : '(根目录)';
  tally.set(top, (tally.get(top) ?? 0) + 1);
}
for (const [top, n] of [...tally].sort((a, b) => b[1] - a[1])) {
  console.log(`   ${top.padEnd(14)} ${n}`);
}
