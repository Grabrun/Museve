#!/bin/bash
# 暮想 Museve 数据库备份脚本
# 用法: ./scripts/backup.sh
# 建议配合 cron: 0 2 * * * /path/to/museve/scripts/backup.sh

# 配置
DB_HOST="127.0.0.1"
DB_NAME="museve"
DB_USER="root"
DB_PASS=""
BACKUP_DIR="$(dirname "$0")/../backups"
KEEP_DAYS=30

# 创建备份目录
mkdir -p "$BACKUP_DIR"

# 文件名
DATE=$(date +%Y%m%d_%H%M%S)
FILE="$BACKUP_DIR/museve_$DATE.sql.gz"

# 备份并压缩
if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" 2>/dev/null | gzip > "$FILE"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null | gzip > "$FILE"
fi

# 检查结果
if [ $? -eq 0 ] && [ -s "$FILE" ]; then
    echo "[$(date)] 备份成功: $FILE ($(du -h "$FILE" | cut -f1))"
else
    echo "[$(date)] 备份失败!" >&2
    rm -f "$FILE"
    exit 1
fi

# 清理旧备份
find "$BACKUP_DIR" -name "museve_*.sql.gz" -mtime +$KEEP_DAYS -delete
echo "[$(date)] 已清理 ${KEEP_DAYS} 天前的备份"
