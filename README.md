# EpycusApp

Sistema multiplataforma de gamificación de hábitos profesionales inspirado en Solo Leveling, enfocado en universitarios peruanos. Incluye gestión de hábitos, temporizador Pomodoro, misiones, progreso con personajes, niveles y un asistente IA (EDY).

**Web:** http://app.epycus.es  
**API:** http://app.epycus.es/swagger  
**Health:** http://app.epycus.es/health

## Stack Tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | ASP.NET Core MVC 9 (C#) |
| ORM | Entity Framework Core 9 + Pomelo.EntityFrameworkCore.MySql |
| Base de datos | MariaDB 11.8 |
| Autenticación | JWT (cookies HttpOnly) + Google OAuth |
| Frontend | Razor Views + Bootstrap 5 + Chart.js + Font Awesome |
| IA | Gemini API 2.5 Flash Lite (asistente EDY) |
| Deploy | VPS Debian 13 (Trixie) + Nginx (reverse proxy) |
| CI/CD | GitHub Actions (build + quality + deploy) |
| Monitoreo | Health checks (BD, Gemini, disco) + TelemetriaMiddleware |

## Estado del Proyecto

| Aspecto | Estado |
|---------|--------|
| Web funcional | ✅ |
| HTTPS | ⚠️ Pendiente (Certbot / Let's Encrypt) |
| App móvil | 📱 En planificación (Flutter) |
| UI/UX | 🎨 En rediseño |
| Tests | ❌ No planificados |

> Ver [PENDIENTES.md](./PENDIENTES.md) para la lista completa de tareas.

## Requisitos

- [.NET 9 SDK](https://dotnet.microsoft.com/download)
- MariaDB 11.8+
- Visual Studio 2026 / VS Code / Rider

## Instalación Local

```bash
# 1. Clonar
git clone https://github.com/Chester0802/EpycusApp.git
cd EpycusApp

# 2. Configurar (usa el ejemplo como template)
cp appsettings.Example.json appsettings.json
# Edita appsettings.json con tus credenciales reales

# Para desarrollo rápido con BD en memoria:
#   Agrega "Database:Provider": "InMemory" en appsettings.json

# 3. Restaurar y migrar
dotnet restore
dotnet ef database update

# 4. Ejecutar
dotnet run
```

La app estará en `http://localhost:5053`.  
Con BD en memoria los datos se resetean al reiniciar.

## Configuración

Copia `appsettings.Example.json` a `appsettings.json` y configura:

| Clave | Descripción | Obligatorio |
|-------|------------|-------------|
| `ConnectionStrings:ConexionPrincipal` | Cadena de conexión a MariaDB | ✅ |
| `MySql:ServerVersion` | Versión del servidor (ej: `11.8.6-mariadb`) | ✅ |
| `Jwt:Clave` | Clave secreta JWT (mínimo 32 caracteres) | ✅ |
| `Google:ClientId` / `ClientSecret` | Credenciales OAuth de Google | ⚠️ Para login con Google |
| `Correo:*` | Configuración SMTP (Gmail App Password) | ⚠️ Para registro/recuperación |
| `Gemini:ApiKey` | API Key de Google Gemini | ⚠️ Para asistente IA |
| `Database:Provider` | `"InMemory"` para desarrollo sin BD | ❌ Opcional |

En producción todas las credenciales se pasan como variables de entorno en el servicio systemd.

## Deploy en VPS

### Servidor de producción

- **VPS:** 147.93.119.193 (Debian 13 Trixie)
- **Dominio:** app.epycus.es
- **Runtime:** ASP.NET Core 9
- **Proxy:** Nginx → Kestrel (localhost:5000)
- **Servicio:** systemd `epycus-web`

### CI/CD automático

El pipeline en `.github/workflows/ci-cd.yml` se ejecuta al hacer push a `main`:

1. **Code Quality** — restore, format check, build con warnings como errores
2. **Build & Publish** — build + `dotnet publish`
3. **Deploy** — backup del deploy actual → SCP → restart service → health check
4. **Security Scan** — Gitleaks (detección de secretos)

### Secretos requeridos en GitHub

| Secreto | Valor |
|---------|-------|
| `VPS_HOST` | `147.93.119.193` |
| `VPS_USER` | `deploy` |
| `VPS_SSH_KEY` | Clave privada SSH (ed25519) |
| `VPS_PORT` | `22` |
| `VPS_APP_PATH` | `/var/www/epycus-web` |

### Deploy manual (SSH)

```bash
# En el VPS
cd /tmp/epycus-build
git pull origin main
sudo systemctl stop epycus-web
dotnet publish EpycusApp.csproj -c Release -o /var/www/epycus-web
sudo chown -R www-data:www-data /var/www/epycus-web
sudo systemctl start epycus-web

# Verificar
sudo systemctl status epycus-web
curl http://localhost:5000/health
```

### Archivos de deploy

| Archivo | Descripción |
|---------|-------------|
| `deploy/setup-vps.sh` | Configuración inicial del VPS (Debian 13) |
| `deploy/epycus-web.service` | Servicio systemd con variables de entorno |
| `deploy/nginx-epycus.conf` | Configuración de Nginx (reverse proxy) |
| `deploy/epycus-web.service.example` | Template del servicio (placeholders) |
| `deploy/setup-vps.sh.example` | Template del setup (placeholders) |

## Endpoints destacados

| Ruta | Descripción |
|------|-------------|
| `/` | Home / Dashboard |
| `/Autenticacion/Login` | Inicio de sesión |
| `/Autenticacion/Registro` | Registro de usuario |
| `/Habitos/*` | Gestión de hábitos |
| `/Pomodoro/*` | Temporizador Pomodoro |
| `/Misiones/*` | Misiones y objetivos |
| `/Progreso/*` | Estadísticas y evolución |
| `/Perfil/*` | Perfil de usuario y personaje |
| `/admin/*` | Panel de administración |
| `/api/*` | API REST (para app móvil futura) |
| `/health` | Health checks (BD, Gemini, disco) |
| `/swagger` | Documentación OpenAPI |

## Base de datos

```
Motor:    MariaDB 11.8
Base:     epycus_db
Usuario:  epicus_user
Server:   localhost:3306
```

### Migraciones

```bash
dotnet ef migrations add NombreMigracion
dotnet ef database update
```

## Licencia

Epycus App © 2026 — Todos los derechos reservados.
