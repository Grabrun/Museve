#!/usr/bin/env bash
#
# Museve 生产部署脚本（覆盖式同步，不删除远端文件）
#
# 用法：
#   DST=/www/wwwroot/example.com bash scripts/deploy.sh
#
# 可选环境变量：
#   SRC  源目录，默认取脚本所在项目的根目录
#   DST  目标目录（必填）
#
# 设计要点：
#   1. 不使用 --delete：避免把生产环境的 uploads/ 删掉；
#   2. 绝不把 docs/、backups/、nginx.conf、工具链同步进 webroot ——
#      这些一旦落在站点根目录就是公开可下载的（docs/ 里甚至有审计报告）；
#   3. 不用 eval 拼命令，逐项传数组，路径含空格也不会炸。

set -euo pipefail

SRC="${SRC:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DST="${DST:-}"

if [[ -z "$DST" ]]; then
    echo "✗ 请通过环境变量 DST 指定目标目录，例如："
    echo "    DST=/www/wwwroot/example.com bash scripts/deploy.sh"
    exit 1
fi

if [[ ! -d "$DST" ]]; then
    echo "✗ 目标目录不存在：$DST"
    exit 1
fi

# 只同步运行期需要的文件。补充规则时优先用"排除"而不是"只包含"，
# 这样以后新增的源码目录不会被漏掉。
EXCLUDES=(
    --exclude='.git/'
    --exclude='.gitignore'
    --exclude='node_modules/'
    # tools/ 下只有清理脚本与构建脚本，nginx 已 deny /tools/，保留以便服务器上执行数据修复
    --exclude='tools/php/'
    --exclude='scripts/'
    --exclude='docs/'
    --exclude='backups/'
    --exclude='uploads/'
    --exclude='includes/config.php'
    --exclude='nginx.conf'
    --exclude='package.json'
    --exclude='package-lock.json'
    --exclude='tailwind.config.js'
    --exclude='resources/css/tailwind.src.css'
    --exclude='*.md'
    --exclude='*.log'
)

echo "🚀 同步 $SRC → $DST"
rsync -av "${EXCLUDES[@]}" "$SRC/" "$DST/"

echo "✅ 部署完成"
echo "   提示：includes/config.php 未同步，生产配置请单独维护"
