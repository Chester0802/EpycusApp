#!/bin/bash
# ============================================================
# Script de monitoreo de uptime para Epycus Web
# Verifica /health y notifica via webhook si está caído
# Compatible con Discord y Telegram
# ============================================================
# Instalación en cron (ejecutar como root):
#   chmod +x /var/www/epycus-web/monitoreo-uptime.sh
#   crontab -e
#   # Cada 5 minutos:
#   */5 * * * * /var/www/epycus-web/monitoreo-uptime.sh
# ============================================================

set -euo pipefail

# Configuración
URL="http://localhost:5000/health"
TIMEOUT=15

# --- WEBHOOKS (configurar al menos uno) ---
# DISCORD_WEBHOOK="https://discord.com/api/webhooks/..."
# TELEGRAM_BOT_TOKEN="..."
# TELEGRAM_CHAT_ID="..."

# Archivos de estado
FLAG_DIR="/var/www/maintenance"
LAST_DOWN_FLAG="${FLAG_DIR}/.last_down"
NOW=$(date +%s)
COOLDOWN_SECONDS=300  # Re-notificar solo después de 5 min

notificar() {
    local mensaje="$1"
    local color="$2"  # 0=verde, 1=rojo

    # Discord
    if [ -n "${DISCORD_WEBHOOK:-}" ]; then
        curl -s -H "Content-Type: application/json" \
            -X POST \
            -d "{\"embeds\": [{\"title\": \"Epycus Web - Monitoreo\", \"description\": \"$mensaje\", \"color\": $color, \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}]}" \
            "$DISCORD_WEBHOOK" > /dev/null 2>&1
    fi

    # Telegram
    if [ -n "${TELEGRAM_BOT_TOKEN:-}" ] && [ -n "${TELEGRAM_CHAT_ID:-}" ]; then
        curl -s -X POST \
            "https://api.telegram.org/bot${TELEGRAM_BOT_TOKEN}/sendMessage" \
            -d "chat_id=${TELEGRAM_CHAT_ID}&text=${mensaje}&parse_mode=HTML" > /dev/null 2>&1
    fi
}

# Verificar cooldown para evitar spam
if [ -f "$LAST_DOWN_FLAG" ]; then
    LAST_DOWN=$(cat "$LAST_DOWN_FLAG")
    ELAPSED=$((NOW - LAST_DOWN))
    if [ $ELAPSED -lt $COOLDOWN_SECONDS ]; then
        exit 0
    fi
fi

# Ejecutar health check
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$TIMEOUT" "$URL" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    # Servicio OK
    if [ -f "$LAST_DOWN_FLAG" ]; then
        rm -f "$LAST_DOWN_FLAG"
        notificar "🟢 <b>Epycus Web recuperado</b> — El servicio responde nuevamente (HTTP 200)" 65280
    fi
else
    # Servicio caído
    echo "$NOW" > "$LAST_DOWN_FLAG"
    notificar "🔴 <b>Epycus Web CAÍDO</b> — Health check respondió HTTP ${HTTP_CODE}
Servicio: epycus-web
Endpoint: ${URL}
Timestamp: $(date '+%Y-%m-%d %H:%M:%S')
Acción sugerida: sudo systemctl status epycus-web" 16711680
fi
