# 🎮 Epycus Web

Sistema multiplataforma de gamificación de hábitos inspirado en Solo Leveling, enfocado en universitarios peruanos.

## 📋 Descripción

Epycus es un sistema de gamificación de hábitos donde los usuarios eligen su carrera profesional, se les asigna un personaje, y avanzan de nivel completando hábitos, misiones y sesiones Pomodoro.

## 🛠️ Stack Tecnológico

- **Backend:** ASP.NET MVC (.NET 9)
- **Base de datos:** MariaDB
- **ORM:** Entity Framework Core 9 + Pomelo
- **Autenticación:** JWT + Google OAuth
- **IA:** Gemini API (EDY - Asistente Virtual)
- **Frontend:** Razor Views + Bootstrap 5 + Chart.js
- **Deploy:** VPS Hostinger (Ubuntu + Nginx)

## 📦 Requisitos

- [.NET 9 SDK](https://dotnet.microsoft.com/download)
- MariaDB 10.x+
- Visual Studio 2026 / VS Code

## 🚀 Instalación Local

```bash
# 1. Clonar el repositorio
git clone https://github.com/Chester0802/Capstone_Epycus_Web.git
cd Capstone_Epycus_Web

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

## ⚙️ Configuración

Copia `appsettings.Example.json` a `appsettings.json` y configura:

| Clave | Descripción |
|---|---|
| `ConnectionStrings:ConexionPrincipal` | Cadena de conexión a MariaDB |
| `MySql:ServerVersion` | Versión del servidor (ej: `11.8.6-mariadb`) |
| `Jwt:Clave` | Clave secreta para JWT (mínimo 32 caracteres) |
| `Google:ClientId` / `ClientSecret` | Credenciales OAuth de Google |
| `Correo:*` | Configuración SMTP para envío de correos |
| `Gemini:ApiKey` | API Key de Google Gemini |

## 🚢 Deploy en VPS

### Requisitos del servidor:
- Ubuntu 22.04+
- .NET 9 SDK
- MariaDB
- Nginx

### Deploy automático (CI/CD):
El deploy se ejecuta automáticamente via GitHub Actions al hacer push a `main`.

### Deploy manual:

```bash
# 1. En el VPS, configurar la base de datos y servicios
sudo bash deploy/setup-vps.sh

# 2. Editar variables de entorno del servicio
sudo nano /etc/systemd/system/epycus-web.service

# 3. Publicar la aplicación (desde tu máquina local)
dotnet publish EpycusApp.csproj -c Release -o ./publish

# 4. Copiar al VPS
scp -r ./publish/* usuario@tu-vps:/var/www/epycus-web/

# 5. En el VPS, iniciar el servicio
sudo systemctl daemon-reload
sudo systemctl restart epycus-web
sudo systemctl status epycus-web
```

### Secretos requeridos en GitHub (para CI/CD):

| Secreto | Descripción |
|---|---|
| `VPS_HOST` | IP o dominio del VPS |
| `VPS_USER` | Usuario SSH |
| `VPS_SSH_KEY` | Clave privada SSH |
| `VPS_PORT` | Puerto SSH (default: 22) |
| `VPS_APP_PATH` | Ruta de la app (ej: `/var/www/epycus-web`) |

### Archivos de deploy incluidos:

| Archivo | Descripción |
|---|---|
| `deploy/epycus-web.service` | Servicio systemd para la app |
| `deploy/nginx-epycus.conf` | Configuración de Nginx (reverse proxy + SSL) |
| `deploy/setup-vps.sh` | Script de configuración inicial del VPS |

## 🗄️ Base de datos

Configuración de MariaDB para producción:

```
Base de datos: epicus_db
Usuario: epicus_user
ServerVersion: 11.8.6-mariadb
```

En `appsettings.json` o variables de entorno:

```json
"MySql": {
  "ServerVersion": "10.11.6-mariadb"
}
```

## 📄 Licencia

Epycus App © 2025 — Todos los derechos reservados.
