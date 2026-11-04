#!/usr/bin/env bash
#
# 暮想 Museve 数据库备份
#
# 用法：
#   DB_NAME=museve DB_USER=museve bash scripts/backup.sh
#
# 凭据建议放在独立文件里（默认读 /etc/museve/backup.env，可用 MUSEVE_BACKUP_ENV 覆盖）：
#   DB_HOST=127.0.0.1
#   DB_NAME=museve
#   DB_USER=museve
#   DB_PASS=********
#   BACKUP_DIR=/var/backups/museve
#
# 与旧版的区别：
#   - 不再写死 root + 空密码；
#   - 备份默认落在 webroot 之外，避免整库导出被当静态文件下载；
#   - 用 MYSQL_PWD 传密码，不出现在 ps 的命令行里；
#   - 开启 pipefail，mysqldump 失败不会再被 gzip 的退出码掩盖。

set -euo pipefail

CONF="${MUSEVE_BACKUP_ENV:-/etc/museve/backup.env}"
if [[ -f "$CONF" ]]; then
    # shellcheck disable=SC1090
    source "$CONF"
fi

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/museve}"
KEEP_DAYS="${KEEP_DAYS:-30}"

if [[ -z "$DB_NAME" || -z "$DB_USER" ]]; then
    echo "✗ 缺少数据库配置：请设置 DB_NAME / DB_USER（或提供 $CONF）" >&2
    exit 1
fi

mkdir -p "$BACKUP_DIR"

DATE="$(date +%Y%m%d_%H%M%S)"
FILE="$BACKUP_DIR/${DB_NAME}_${DATE}.sql.gz"

export MYSQL_PWD="$DB_PASS"
mysqldump --single-transaction --quick --no-tablespaces \
    -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" | gzip > "$FILE"
unset MYSQL_PWD

if [[ ! -s "$FILE" ]]; then
    echo "[$(date '+%F %T')] 备份失败：文件为空" >&2
    rm -f "$FILE"
    exit 1
fi

echo "[$(date '+%F %T')] 备份成功: $FILE ($(du -h "$FILE" | cut -f1))"

# 站点根目录内的备份是公开可下载的，给个显式警告
if [[ -n "${WEBROOT:-}" && "$BACKUP_DIR" == "$WEBROOT"* ]]; then
    echo "⚠️  警告：BACKUP_DIR 位于 webroot 内（$WEBROOT），数据库导出可被直接下载，请移出站点目录" >&2
fi

find "$BACKUP_DIR" -name "${DB_NAME}_*.sql.gz" -mtime +"$KEEP_DAYS" -delete
echo "[$(date '+%F %T')] 已清理 ${KEEP_DAYS} 天前的备份"
