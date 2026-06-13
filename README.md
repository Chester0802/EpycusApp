# 🎮 Epycus Web

Sistema multiplataforma de gamificación de hábitos inspirado en Solo Leveling, enfocado en universitarios peruanos.

## 📋 Descripción

Epycus es un sistema de gamificación de hábitos donde los usuarios eligen su carrera profesional, se les asigna un personaje, y avanzan de nivel completando hábitos, misiones y sesiones Pomodoro.

## 🛠️ Stack Tecnológico

- **Backend:** ASP.NET MVC (.NET 10)
- **Base de datos:** MariaDB / MySQL
- **ORM:** Entity Framework Core 9
- **Autenticación:** JWT + Google OAuth
- **IA:** Gemini API
- **Frontend:** Razor Views + Bootstrap 5 + Chart.js
- **Deploy:** VPS Hostinger (Ubuntu + Nginx)

## 📦 Requisitos

- [.NET 10 SDK](https://dotnet.microsoft.com/download)
- MariaDB o MySQL
- Visual Studio 2026 / VS Code

## 🚀 Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/Chester0802/Capstone_Epycus_Web.git
cd Capstone_Epycus_Web

# 2. Configurar appsettings
cp appsettings.Example.json appsettings.Development.json
# Editar appsettings.Development.json con tus credenciales

# 3. Restaurar dependencias
dotnet restore

# 4. Aplicar migraciones
dotnet ef database update

# 5. Ejecutar la aplicación
dotnet run
```

La aplicación estará disponible en: `http://localhost:5053`

## ⚙️ Configuración

Copia `appsettings.Example.json` a `appsettings.Development.json` y configura:

| Clave | Descripción |
|---|---|
| `ConnectionStrings:ConexionPrincipal` | Cadena de conexión a MariaDB/MySQL |
| `Jwt:Clave` | Clave secreta para JWT (mínimo 32 caracteres) |
| `Google:ClientId` / `ClientSecret` | Credenciales OAuth de Google |
| `Correo:*` | Configuración SMTP para envío de correos |
| `Gemini:ApiKey` | API Key de Google Gemini |

## 🚢 Deploy

El deploy se ejecuta automáticamente via GitHub Actions al hacer push a `main`.

### Secretos requeridos en GitHub:

| Secreto | Descripción |
|---|---|
| `VPS_HOST` | IP o dominio del VPS |
| `VPS_USER` | Usuario SSH |
| `VPS_SSH_KEY` | Clave privada SSH |
| `VPS_PORT` | Puerto SSH (default: 22) |
| `VPS_APP_PATH` | Ruta de la aplicación en el VPS |

## 📄 Licencia

Epycus App © 2025 — Todos los derechos reservados.
