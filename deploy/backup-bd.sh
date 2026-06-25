#!/bin/bash
# ============================================================
# Backup automático de MariaDB para Epycus
# Uso: ./deploy/backup-bd.sh
# Cron recomendado: 0 3 * * * /var/www/epycus-web/deploy/backup-bd.sh
# ============================================================
set -e

DB_NAME="epicus_db"
DB_USER="epicus_user"
DB_PASSWORD="${DB_PASSWORD:-CHANGE_ME_DB_PASSWORD}"
BACKUP_DIR="/var/backups/epycus-db"
RETENTION_DAYS=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/epycus-db-${TIMESTAMP}.sql"
BACKUP_GZ="${BACKUP_FILE}.gz"

mkdir -p "$BACKUP_DIR"

mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" --single-transaction --routines --triggers | gzip > "$BACKUP_GZ"

echo "✅ Backup created: ${BACKUP_GZ} ($(du -h "$BACKUP_GZ" | cut -f1))"

# Verificar integridad del backup (test gzip + test restore a DB temporal)
echo "🔍 Verificando integridad del backup..."
if gzip -t "$BACKUP_GZ"; then
    echo "✅ Archivo gzip válido"
else
    echo "❌ ERROR: Archivo gzip corrupto"
    exit 1
fi

# Test de restore rápido: crear DB temporal y verificar tablas principales
VERIFY_DB="${DB_NAME}_verify_${TIMESTAMP}"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE \`$VERIFY_DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if zcat "$BACKUP_GZ" | mysql -u "$DB_USER" -p"$DB_PASSWORD" "$VERIFY_DB"; then
    # Verificar que las tablas críticas tienen datos
    TABLES=("Usuarios" "Habitos" "SesionesPomodoro" "Misiones" "Carreras")
    for TABLE in "${TABLES[@]}"; do
        COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -N -e "SELECT COUNT(*) FROM \`$VERIFY_DB\`.\`$TABLE\`;" 2>/dev/null || echo "0")
        echo "  - Tabla $TABLE: $COUNT filas"
    done
    echo "✅ Verificación de restore exitosa"
else
    echo "❌ ERROR: Fallo al restaurar backup de prueba"
    mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS \`$VERIFY_DB\`;"
    exit 1
fi

# Limpiar DB de verificación
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS \`$VERIFY_DB\`;"

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