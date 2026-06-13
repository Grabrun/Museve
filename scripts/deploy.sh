#!/bin/bash
# Museve 生产部署脚本
# 用法: bash scripts/deploy.sh
# ⚠️ 注意: 
#   - 禁用 --delete，避免删除生产环境上传文件
#   - excludes 中的文件不会被同步（config.php 各环境不同）

SRC="/root/.openclaw/workspace/Museve/"
DST="/www/wwwroot/memory.grabrun.top/"
EXCLUDES="--exclude='.git/' --exclude='node_modules/' --exclude='scripts/' --exclude='uploads/' --exclude='includes/config.php'"

echo "🚀 同步 Museve 到生产环境..."
eval rsync -av "$EXCLUDES" "$SRC" "$DST"
echo "✅ 部署完成！"
