# EpycusApp

Sistema multiplataforma de gamificación de hábitos inspirado en Solo Leveling, enfocado en universitarios peruanos.

## Stack Tecnológico

- **Backend:** ASP.NET MVC (.NET 9)
- **Base de datos:** MariaDB 11.8
- **ORM:** Entity Framework Core 9 + Pomelo
- **Autenticación:** JWT + Google OAuth
- **IA:** Gemini API (EDY - Asistente Virtual)
- **Frontend:** Razor Views + Bootstrap 5 + Chart.js
- **Deploy:** VPS Debian 13 (Trixie) + Nginx
- **CI/CD:** GitHub Actions

## URL de Producción

- **Web:** http://app.epycus.es

## Requisitos

- [.NET 9 SDK](https://dotnet.microsoft.com/download)
- MariaDB 11.8+
- Visual Studio 2026 / VS Code

## Instalación Local

```bash
# 1. Clonar el repositorio
git clone https://github.com/Chester0802/EpycusApp.git
cd EpycusApp

# 2. Configurar appsettings
cp appsettings.Example.json appsettings.json
# Editar appsettings.json con tus credenciales

# 3. Restaurar dependencias
dotnet restore

# 4. Aplicar migraciones
dotnet ef database update

# 5. Ejecutar la aplicación
dotnet run
```

La aplicación estará disponible en: `http://localhost:5053`

## Configuración

Copia `appsettings.Example.json` a `appsettings.json` y configura:

| Clave | Descripción |
|---|---|
| `ConnectionStrings:ConexionPrincipal` | Cadena de conexión a MariaDB |
| `MySql:ServerVersion` | Versión del servidor (ej: `11.8.6-mariadb`) |
| `Jwt:Clave` | Clave secreta para JWT (mínimo 32 caracteres) |
| `Google:ClientId` / `ClientSecret` | Credenciales OAuth de Google |
| `Correo:*` | Configuración SMTP para envío de correos |
| `Gemini:ApiKey` | API Key de Google Gemini |

## Deploy en VPS

### Datos del servidor:
- **VPS:** 147.93.119.193 (Debian 13 Trixie)
- **Dominio:** app.epycus.es
- **Base de datos:** epicus_db / epicus_user
- **Runtime:** ASP.NET Core 9
- **Reverse Proxy:** Nginx → Kestrel (localhost:5000)
- **Servicio:** systemd `epycus-web`

### Deploy automático (CI/CD):
El deploy se ejecuta automáticamente via GitHub Actions al hacer push a `main`.

### Deploy manual:

```bash
# 1. En el VPS, ejecutar el script de configuración
sudo bash deploy/setup-vps.sh

# 2. Configurar el servicio con credenciales reales
sudo nano /etc/systemd/system/epycus-web.service

# 3. Deploy inicial
git clone https://github.com/Chester0802/EpycusApp.git /tmp/epycus-build
cd /tmp/epycus-build
dotnet publish EpycusApp.csproj -c Release -o /var/www/epycus-web
sudo chown -R www-data:www-data /var/www/epycus-web
sudo systemctl daemon-reload
sudo systemctl start epycus-web

# 4. Verificar
sudo systemctl status epycus-web
curl http://localhost:5000/health
```

### Secretos requeridos en GitHub (para CI/CD):

| Secreto | Descripción |
|---|---|
| `VPS_HOST` | `147.93.119.193` |
| `VPS_USER` | `deploy` |
| `VPS_SSH_KEY` | Clave privada SSH (ed25519) |
| `VPS_PORT` | `22` |
| `VPS_APP_PATH` | `/var/www/epycus-web` |

### Archivos de deploy incluidos:

| Archivo | Descripción |
|---|---|
| `deploy/epycus-web.service.example` | Servicio systemd (template con placeholders) |
| `deploy/nginx-epycus.conf` | Configuración de Nginx (reverse proxy) |
| `deploy/setup-vps.sh.example` | Script de configuración inicial del VPS (Debian 13) |

## Base de datos

Configuración de MariaDB para producción:

```
Base de datos: epicus_db
Usuario: epicus_user
ServerVersion: 11.8.6-mariadb
```

## Licencia

Epycus App © 2025 — Todos los derechos reservados.
