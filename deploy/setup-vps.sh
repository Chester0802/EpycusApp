#!/bin/bash
# ============================================================
# Script de configuración inicial del VPS para Epycus Web
# Ejecutar como root o con sudo en Ubuntu
# ============================================================

set -e

echo "=== 1. Configurando MariaDB ==="

# Crear base de datos y usuario (si no existen)
mysql -u root <<EOF
CREATE DATABASE IF NOT EXISTS epicus_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'epicus_user'@'localhost' IDENTIFIED BY '***REMOVED***';
GRANT ALL PRIVILEGES ON epicus_db.* TO 'epicus_user'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "Base de datos epicus_db creada exitosamente."

echo "=== 2. Instalando Nginx ==="
apt update
apt install -y nginx

echo "=== 3. Configurando directorio de la aplicación ==="
mkdir -p /var/www/epycus-web
chown -R www-data:www-data /var/www/epycus-web

echo "=== 4. Copiando configuración de Nginx ==="
cp deploy/nginx-epycus.conf /etc/nginx/sites-available/epycus-web
ln -sf /etc/nginx/sites-available/epycus-web /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "=== 5. Configurando servicio systemd ==="
cp deploy/epycus-web.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable epycus-web

echo "=== 6. Instalando Certbot para SSL (opcional) ==="
apt install -y certbot python3-certbot-nginx
echo "Para obtener certificado SSL ejecuta:"
echo "  sudo certbot --nginx -d your-domain.com -d www.your-domain.com"

echo ""
echo "=== CONFIGURACIÓN COMPLETADA ==="
echo ""
echo "IMPORTANTE: Edita el archivo /etc/systemd/system/epycus-web.service"
echo "y reemplaza los valores de las variables de entorno con tus datos reales:"
echo "  - Jwt__Clave (genera una clave aleatoria de 32+ caracteres)"
echo "  - Google__ClientId y Google__ClientSecret"
echo "  - Correo__* (credenciales SMTP)"
echo "  - Gemini__ApiKey"
echo "  - App__UrlBase (tu dominio)"
echo ""
echo "Luego ejecuta:"
echo "  sudo systemctl daemon-reload"
echo "  sudo systemctl start epycus-web"
echo "  sudo systemctl status epycus-web"
