#!/bin/bash
# ============================================================
# Script para activar/desactivar modo mantenimiento en Epycus Web
# ============================================================
# Uso:
#   sudo ./deploy/maintenance.sh on    # Activar mantenimiento (503)
#   sudo ./deploy/maintenance.sh off   # Desactivar mantenimiento
#   sudo ./deploy/maintenance.sh status # Ver estado actual
# ============================================================

set -euo pipefail

FLAG_FILE="/var/www/maintenance/maintenance.flag"
NGINX_SERVICE="nginx"

case "${1:-status}" in
    on)
        echo "🔧 Activando modo mantenimiento..."
        mkdir -p "$(dirname "$FLAG_FILE")"
        date '+%Y-%m-%d %H:%M:%S' > "$FLAG_FILE"
        echo "✅ Mantenimiento ACTIVADO — Nginx mostrará página 503"
        ;;
    off)
        echo "🔧 Desactivando modo mantenimiento..."
        if [ -f "$FLAG_FILE" ]; then
            rm -f "$FLAG_FILE"
            echo "✅ Mantenimiento DESACTIVADO — Tráfico reanudado"
        else
            echo "ℹ️  No hay mantenimiento activo"
        fi
        ;;
    status)
        if [ -f "$FLAG_FILE" ]; then
            echo "🟡 MODO MANTENIMIENTO ACTIVO (desde $(cat "$FLAG_FILE"))"
        else
            echo "🟢 Servicio operando normalmente"
        fi
        ;;
    *)
        echo "Uso: $0 {on|off|status}"
        exit 1
        ;;
esac
