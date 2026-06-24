#!/bin/bash
# ============================================================
# Backup automático de MariaDB para Epycus
# Uso: ./deploy/backup-bd.sh
# Cron recomendado: 0 3 * * * /var/www/epycus-web/deploy/backup-bd.sh
# ============================================================
set -e

DB_NAME="epicus_db"
DB_USER="epicus_user"
DB_PASSWORD="ROTATED_DB_PASSWORD"
BACKUP_DIR="/var/backups/epycus-db"
RETENTION_DAYS=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/epycus-db-${TIMESTAMP}.sql"
BACKUP_GZ="${BACKUP_FILE}.gz"

mkdir -p "$BACKUP_DIR"

mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --single-transaction --routines --triggers | gzip > "$BACKUP_GZ"

echo "✅ Backup created: ${BACKUP_GZ} ($(du -h "$BACKUP_GZ" | cut -f1))"

# Cleanup old backups
find "$BACKUP_DIR" -name "epycus-db-*.sql.gz" -mtime +$RETENTION_DAYS -delete

# Keep at least last 7 backups regardless of date
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/*.sql.gz 2>/dev/null | wc -l)
MAX_BACKUPS=30
if [ "$BACKUP_COUNT" -gt "$MAX_BACKUPS" ]; then
    ls -t "$BACKUP_DIR"/*.sql.gz | tail -n +$((MAX_BACKUPS + 1)) | xargs -r rm
fi

echo "✅ Retention: keeping last ${RETENTION_DAYS} days (max ${MAX_BACKUPS} backups)"
echo "📂 Backup dir: ${BACKUP_DIR}"
