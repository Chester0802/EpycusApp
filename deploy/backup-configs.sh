#!/bin/bash
# ============================================================
# Backup de configuraciones del sistema para Epycus
# Respalda: nginx, systemd, nftables, sudoers, scripts deploy
# Uso: ./deploy/backup-configs.sh
# Cron recomendado: 0 4 * * * /var/www/epycus-web/deploy/backup-configs.sh
# ============================================================
set -e

BACKUP_DIR="/var/backups/epycus-configs"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/epycus-configs-${TIMESTAMP}.tar.gz"
RETENTION_DAYS=7
MAX_BACKUPS=30

mkdir -p "$BACKUP_DIR"

echo "📦 Creando backup de configuraciones del sistema..."

# Archivos y directorios a respaldar
CONFIG_PATHS=(
    "/etc/nginx/sites-available/epycus-web"
    "/etc/nginx/sites-enabled/epycus-web"
    "/etc/systemd/system/epycus-web.service"
    "/etc/nftables.conf"
    "/etc/sudoers.d/deploy"
    "/etc/systemd/journald.conf.d/epycus.conf"
    "/var/www/epycus-web/deploy"
    "/var/www/maintenance"
)

# Verificar que existen y crear tar.gz
EXISTING_PATHS=()
for PATH_ITEM in "${CONFIG_PATHS[@]}"; do
    if [ -e "$PATH_ITEM" ]; then
        EXISTING_PATHS+=("$PATH_ITEM")
    else
        echo "⚠️  No existe: $PATH_ITEM (se omite)"
    fi
done

if [ ${#EXISTING_PATHS[@]} -eq 0 ]; then
    echo "❌ No hay archivos de configuración para respaldar"
    exit 1
fi

tar -czf "$BACKUP_FILE" "${EXISTING_PATHS[@]}" 2>/dev/null

echo "✅ Backup creado: ${BACKUP_FILE} ($(du -h "$BACKUP_FILE" | cut -f1))"

# Verificar integridad
if tar -tzf "$BACKUP_FILE" >/dev/null 2>&1; then
    echo "✅ Archivo tar.gz válido"
else
    echo "❌ ERROR: Archivo tar.gz corrupto"
    exit 1
fi

# Cleanup old backups
find "$BACKUP_DIR" -name "epycus-configs-*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Keep at least last 30 backups regardless of date
BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/*.tar.gz 2>/dev/null | wc -l)
if [ "$BACKUP_COUNT" -gt "$MAX_BACKUPS" ]; then
    ls -t "$BACKUP_DIR"/*.tar.gz | tail -n +$((MAX_BACKUPS + 1)) | xargs -r rm
fi

echo "✅ Retention: keeping last ${RETENTION_DAYS} days (max ${MAX_BACKUPS} backups)"
echo "📂 Backup dir: ${BACKUP_DIR}"