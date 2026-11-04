/**
 * PHP 语法校验（node tools/lint-php.mjs）
 *
 * 本机没有 PHP 二进制，用 php-parser 做静态语法门禁：
 * 只验证能否解析，不做语义检查。CI / 本地提交流程都可复用。
 */
import fs from 'node:fs';
import path from 'node:path';
import { Engine } from 'php-parser';

const ROOT = path.resolve(process.cwd());
const SKIP_DIRS = new Set(['node_modules', 'vendor', '.git', 'uploads', 'backups', '.workbuddy']);

const parser = new Engine({
  parser: { extractDoc: false, suppressErrors: false, version: 801 },
  ast: { withPositions: true },
  lexer: { all_tokens: false, comment_tokens: false },
});

/** @type {string[]} */
const files = [];
(function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.isDirectory()) {
      if (SKIP_DIRS.has(entry.name)) continue;
      walk(path.join(dir, entry.name));
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(path.join(dir, entry.name));
    }
  }
})(ROOT);

let failed = 0;
for (const file of files.sort()) {
  const rel = path.relative(ROOT, file).replace(/\\/g, '/');
  const code = fs.readFileSync(file, 'utf8');
  try {
    parser.parseCode(code, rel);
  } catch (err) {
    failed++;
    const line = err?.loc?.start?.line ?? '?';
    console.error(`✗ ${rel}:${line}  ${String(err.message).split('\n')[0]}`);
  }
}

if (failed === 0) {
  console.log(`✓ PHP 语法校验通过（${files.length} 个文件）`);
  process.exit(0);
} else {
  console.error(`\n✗ ${failed} / ${files.length} 个文件存在语法错误`);
  process.exit(1);
}
