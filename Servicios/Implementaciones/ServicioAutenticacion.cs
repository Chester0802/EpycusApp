using EpycusApp.Datos;
using EpycusApp.Servicios.Interfaces;
using EpycusApp.ViewModels;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;

namespace EpycusApp.Servicios.Implementaciones
{
    public class ServicioAutenticacion : IServicioAutenticacion
    {
        private readonly ContextoAplicacion _contexto;
        private readonly IConfiguration _config;
        private readonly IServicioCorreo _servicioCorreo;
        private readonly ILogger<ServicioAutenticacion> _logger;

        public ServicioAutenticacion(
            ContextoAplicacion contexto,
            IConfiguration config,
            IServicioCorreo servicioCorreo,
            ILogger<ServicioAutenticacion> logger)
        {
            _contexto = contexto;
            _config = config;
            _servicioCorreo = servicioCorreo;
            _logger = logger;
        }

        public Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RegistrarUsuario(RegistroViewModel modelo)
        {
            return RegistrarUsuarioInterno(modelo);
        }

        public Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RenovarToken(string refreshToken)
        {
            return RenovarTokenInterno(refreshToken);
        }

        private async Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RegistrarUsuarioInterno(RegistroViewModel modelo)
        {
            var existeCorreo = await _contexto.Usuarios
                .AnyAsync(u => u.CorreoElectronico == modelo.CorreoElectronico);

            if (existeCorreo)
            {
                return (false, "El correo ya está registrado", null, null);
            }

            var rolUsuario = await _contexto.Roles.FirstOrDefaultAsync(r => r.Nombre == "Usuario");
            if (rolUsuario == null)
            {
                return (false, "No existe el rol base de usuario", null, null);
            }

            string codigoUnico;
            do
            {
                codigoUnico = Ayudantes.GeneradorCodigo.GenerarCodigoUsuario();
            } while (await _contexto.Usuarios.AnyAsync(u => u.CodigoUnico == codigoUnico));

            var hash = BCrypt.Net.BCrypt.HashPassword(modelo.Contrasena, workFactor: 12);

            var usuario = new Models.Entidades.Usuario
            {
                CodigoUnico = codigoUnico,
                Nombre = modelo.Nombre,
                CorreoElectronico = modelo.CorreoElectronico,
                ContrasenaHash = hash,
                FechaNacimiento = modelo.FechaNacimiento,
                Genero = modelo.Genero,
                RolId = rolUsuario.Id,
                CarreraId = modelo.CarreraId,
                CorreoVerificado = false,
                AceptoTerminos = modelo.AceptoTerminos,
                EstaActivo = true,
                FechaRegistro = DateTime.UtcNow
            };

            _contexto.Usuarios.Add(usuario);
            await _contexto.SaveChangesAsync();

            var nivelInicial = await _contexto.Niveles.FirstOrDefaultAsync(n => n.Numero == 0);
            if (nivelInicial != null)
            {
                _contexto.ProgresosUsuario.Add(new Models.Entidades.ProgresoUsuario
                {
                    UsuarioId = usuario.Id,
                    NivelActualId = nivelInicial.Id,
                    XpTotal = 0,
                    RachaActual = 0,
                    RachaMaxima = 0
                });
            }

            _contexto.ConfiguracionesPomodoro.Add(new Models.Entidades.ConfiguracionPomodoro
            {
                UsuarioId = usuario.Id,
                TiempoEstudioMin = 25,
                TiempoDescansoMin = 5,
                TiempoDescansoLargoMin = 15,
                CiclosAntesDescansoLargo = 4,
                SonidoActivo = true,
                FechaActualizacion = DateTime.UtcNow
            });

            await _contexto.SaveChangesAsync();

            var token = GenerarToken(usuario);
            var refreshToken = GenerarRefreshToken();
                        var refreshTokenHash = HashToken(refreshToken);

                        _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
                        {
                            UsuarioId = usuario.Id,
                            Token = refreshTokenHash,
                            ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
                        });

                        await _contexto.SaveChangesAsync();

                        return (true, "Registro exitoso", token, refreshToken);
        }

        private async Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> RenovarTokenInterno(string refreshToken)
        {
            if (string.IsNullOrWhiteSpace(refreshToken))
            {
                return (false, "Token inválido", null, null);
            }

            var refreshHash = HashToken(refreshToken);

            await using var transaction = await _contexto.Database.BeginTransactionAsync();
            try
            {
                var tokenGuardado = await _contexto.TokensRefresh
                    .FirstOrDefaultAsync(t => t.Token == refreshHash && !t.Revocado);

                if (tokenGuardado == null || tokenGuardado.ExpiraEn < DateTime.UtcNow)
                {
                    return (false, "Token expirado", null, null);
                }

                var usuario = await _contexto.Usuarios
                    .Include(u => u.Rol)
                    .FirstOrDefaultAsync(u => u.Id == tokenGuardado.UsuarioId);

                if (usuario == null || !usuario.EstaActivo)
                {
                    return (false, "Usuario inválido", null, null);
                }

                tokenGuardado.Revocado = true;
                await _contexto.SaveChangesAsync();

                var nuevoRefresh = GenerarRefreshToken();
                var nuevoRefreshHash = HashToken(nuevoRefresh);

                _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
                {
                    UsuarioId = usuario.Id,
                    Token = nuevoRefreshHash,
                    ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
                });

                var token = GenerarToken(usuario);
                await _contexto.SaveChangesAsync();

                await transaction.CommitAsync();

                return (true, "Token renovado", token, nuevoRefresh);
            }
            catch
            {
                await transaction.RollbackAsync();
                throw;
            }
        }

        public async Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> Login(string correo, string contrasena)
        {
            var usuario = await _contexto.Usuarios
                .Include(u => u.Rol)
                .FirstOrDefaultAsync(u => u.CorreoElectronico == correo);

            if (usuario == null)
            {
                return (false, "Credenciales incorrectas", null, null);
            }

            if (!usuario.EstaActivo)
            {
                return (false, "La cuenta está desactivada", null, null);
            }

            if (usuario.BloqueoHasta.HasValue && usuario.BloqueoHasta > DateTime.UtcNow)
            {
                var minutosRestantes = (int)(usuario.BloqueoHasta.Value - DateTime.UtcNow).TotalMinutes;
                return (false, $"Cuenta bloqueada. Intenta de nuevo en {minutosRestantes} minuto(s).", null, null);
            }

            if (string.IsNullOrWhiteSpace(usuario.ContrasenaHash))
            {
                return (false, "La cuenta no tiene contraseña, inicia sesión con Google", null, null);
            }

            if (!BCrypt.Net.BCrypt.Verify(contrasena, usuario.ContrasenaHash))
            {
                usuario.IntentosFallidos++;
                if (usuario.IntentosFallidos >= 5)
                {
                    usuario.BloqueoHasta = DateTime.UtcNow.AddMinutes(15);
                    usuario.IntentosFallidos = 0;
                }
                await _contexto.SaveChangesAsync();
                return (false, "Credenciales incorrectas", null, null);
            }

            usuario.IntentosFallidos = 0;
            usuario.BloqueoHasta = null;
            var token = GenerarToken(usuario);
            var refreshToken = GenerarRefreshToken();

            usuario.UltimoAcceso = DateTime.UtcNow;

            var refreshTokenHash = HashToken(refreshToken);
            _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
            {
                UsuarioId = usuario.Id,
                Token = refreshTokenHash,
                ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
            });

            await _contexto.SaveChangesAsync();

            return (true, "Login exitoso", token, refreshToken);
        }

        public async Task<(bool EsExitoso, string? Mensaje)> CambiarContrasenaAsync(string correo, string contrasenaActual, string nuevaContrasena)
        {
                    var usuario = await _contexto.Usuarios.FirstOrDefaultAsync(u => u.CorreoElectronico == correo);
                    if (usuario == null)
                        return (false, "Usuario no encontrado");

                    if (string.IsNullOrWhiteSpace(usuario.ContrasenaHash))
                        return (false, "La cuenta no permite cambio de contraseña (inicia sesiÃ³n con Google)");

                    if (!BCrypt.Net.BCrypt.Verify(contrasenaActual, usuario.ContrasenaHash))
                        return (false, "Contraseña actual incorrecta");

                    usuario.ContrasenaHash = BCrypt.Net.BCrypt.HashPassword(nuevaContrasena, workFactor: 12);
                    await _contexto.SaveChangesAsync();
                    return (true, null);
                }

                private int ObtenerExpiracionRefreshDias()
                {
                    var expiracion = _config["Jwt:ExpiracionRefreshDias"];
                    return int.TryParse(expiracion, out var dias) ? dias : 7;
                }

        private string GenerarRefreshToken()
        {
                    // Genera un token criptográficamente seguro y URL-safe
                    var bytes = new byte[32];
                    using (var rng = RandomNumberGenerator.Create())
                    {
                        rng.GetBytes(bytes);
                    }
                    var token = Convert.ToBase64String(bytes).TrimEnd('=')
                        .Replace('+', '-')
                        .Replace('/', '_');
                    return token;
                }

                private static string HashToken(string token)
                {
                    using var sha = SHA256.Create();
                    var bytes = sha.ComputeHash(Encoding.UTF8.GetBytes(token));
                    return BitConverter.ToString(bytes).Replace("-", "").ToLowerInvariant();
                }

                private string GenerarToken(Models.Entidades.Usuario usuario)
                {
            var claims = new[]
            {
                new Claim(ClaimTypes.NameIdentifier, usuario.Id.ToString()),
                new Claim(ClaimTypes.Email, usuario.CorreoElectronico),
                new Claim(ClaimTypes.Name, usuario.Nombre),
                new Claim(ClaimTypes.Role, usuario.Rol?.Nombre ?? "Usuario"),
                new Claim("CodigoUnico", usuario.CodigoUnico),
                new Claim("CarreraId", usuario.CarreraId.ToString())
            };

            var claveStr = _config["Jwt:Clave"];
            if (string.IsNullOrEmpty(claveStr))
                throw new InvalidOperationException("La clave secreta de JWT no está configurada.");

            var clave = new SymmetricSecurityKey(System.Text.Encoding.UTF8.GetBytes(claveStr));
            var credenciales = new SigningCredentials(clave, SecurityAlgorithms.HmacSha256);

            var expiracionStr = _config["Jwt:ExpiracionMinutos"];
            var minutos = int.TryParse(expiracionStr, out int val) ? val : 60;

            var token = new JwtSecurityToken(
                issuer: _config["Jwt:Emisor"],
                audience: _config["Jwt:Audiencia"],
                claims: claims,
                expires: DateTime.UtcNow.AddMinutes(minutos),
                signingCredentials: credenciales
            );

            return new JwtSecurityTokenHandler().WriteToken(token);
        }

        public Task<bool> VerificarCorreo(string token)
        {
            return VerificarCorreoInterno(token);
        }

        private async Task<bool> VerificarCorreoInterno(string token)
        {
            var verificacion = await _contexto.VerificacionesCorreo
                .FirstOrDefaultAsync(v => v.Token == token && !v.Usado);

            if (verificacion == null || verificacion.ExpiraEn < DateTime.UtcNow)
            {
                return false;
            }

            var usuario = await _contexto.Usuarios.FirstOrDefaultAsync(u => u.Id == verificacion.UsuarioId);
            if (usuario == null)
            {
                return false;
            }

            usuario.CorreoVerificado = true;
            verificacion.Usado = true;
            await _contexto.SaveChangesAsync();

            await _servicioCorreo.EnviarBienvenida(usuario.CorreoElectronico, usuario.Nombre);

            return true;
        }

        public Task<bool> EnviarCorreoRecuperacion(string correo)
        {
            return EnviarCorreoRecuperacionInterno(correo);
        }

        private async Task<bool> EnviarCorreoRecuperacionInterno(string correo)
        {
            var usuario = await _contexto.Usuarios.FirstOrDefaultAsync(u => u.CorreoElectronico == correo);
            if (usuario == null)
            {
                return false;
            }

            var token = Guid.NewGuid().ToString("N");

            _contexto.RecuperacionesContrasena.Add(new Models.Entidades.RecuperacionContrasena
            {
                UsuarioId = usuario.Id,
                Token = token,
                ExpiraEn = DateTime.UtcNow.AddHours(1),
                Usado = false,
                FechaCreacion = DateTime.UtcNow
            });

            await _contexto.SaveChangesAsync();

            return true;
        }

        public Task<bool> RestablecerContrasena(string token, string nuevaContrasena)
        {
            return RestablecerContrasenaInterno(token, nuevaContrasena);
        }

        private async Task<bool> RestablecerContrasenaInterno(string token, string nuevaContrasena)
        {
            var recuperacion = await _contexto.RecuperacionesContrasena
                .FirstOrDefaultAsync(r => r.Token == token && !r.Usado);

            if (recuperacion == null || recuperacion.ExpiraEn < DateTime.UtcNow)
            {
                return false;
            }

            var usuario = await _contexto.Usuarios.FirstOrDefaultAsync(u => u.Id == recuperacion.UsuarioId);
            if (usuario == null)
            {
                return false;
            }

            usuario.ContrasenaHash = BCrypt.Net.BCrypt.HashPassword(nuevaContrasena, workFactor: 12);
            recuperacion.Usado = true;

            await _contexto.SaveChangesAsync();

            return true;
        }

        public Task CerrarSesion(int usuarioId)
        {
            return CerrarSesionInterno(usuarioId);
        }

        private async Task CerrarSesionInterno(int usuarioId)
        {
            var tokens = await _contexto.TokensRefresh
                .Where(t => t.UsuarioId == usuarioId && !t.Revocado)
                .ToListAsync();

            foreach (var token in tokens)
            {
                token.Revocado = true;
            }

            await _contexto.SaveChangesAsync();
        }

        public async Task<List<EpycusApp.Models.Entidades.Carrera>> ObtenerCarrerasActivas()
        {
            return await _contexto.Carreras
                .Where(c => c.EstaActiva)
                .OrderBy(c => c.Nombre)
                .ToListAsync();
        }

        public async Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> ProcesarAutenticacionGoogleAsync(
            string googleId, string correo, string nombre, string? fotoUrl)
        {
            if (string.IsNullOrWhiteSpace(googleId) || string.IsNullOrWhiteSpace(correo))
                return (false, "Datos de Google inválidos", null, null);

            var usuario = await _contexto.Usuarios
                .Include(u => u.Rol)
                .FirstOrDefaultAsync(u => u.GoogleId == googleId);

            if (usuario != null)
            {
                if (!usuario.EstaActivo)
                    return (false, "La cuenta está desactivada", null, null);

                usuario.UltimoAcceso = DateTime.UtcNow;
                await _contexto.SaveChangesAsync();

                var token = GenerarToken(usuario);
                var refreshToken = GenerarRefreshToken();
                var refreshHash = HashToken(refreshToken);

                _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
                {
                    UsuarioId = usuario.Id,
                    Token = refreshHash,
                    ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
                });
                await _contexto.SaveChangesAsync();

                return (true, "Login exitoso", token, refreshToken);
            }

            usuario = await _contexto.Usuarios
                .Include(u => u.Rol)
                .FirstOrDefaultAsync(u => u.CorreoElectronico == correo);

            if (usuario != null)
            {
                usuario.GoogleId = googleId;
                usuario.FotoGoogleUrl = fotoUrl;
                usuario.UltimoAcceso = DateTime.UtcNow;
                await _contexto.SaveChangesAsync();

                var token = GenerarToken(usuario);
                var refreshToken = GenerarRefreshToken();
                var refreshHash = HashToken(refreshToken);

                _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
                {
                    UsuarioId = usuario.Id,
                    Token = refreshHash,
                    ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
                });
                await _contexto.SaveChangesAsync();

                return (true, "Login exitoso", token, refreshToken);
            }

            return (false, "completar_registro", null, null);
        }

        public async Task<(bool Exito, string Mensaje, string? Token, string? RefreshToken)> CompletarRegistroGoogleAsync(
            CompletarRegistroGoogleViewModel modelo)
        {
            var existeCorreo = await _contexto.Usuarios
                .AnyAsync(u => u.CorreoElectronico == modelo.CorreoElectronico);

            if (existeCorreo)
            {
                return (false, "El correo ya está registrado", null, null);
            }

            var existeGoogleId = await _contexto.Usuarios
                .AnyAsync(u => u.GoogleId == modelo.GoogleId);

            if (existeGoogleId)
            {
                return (false, "Esta cuenta de Google ya está vinculada a otro usuario", null, null);
            }

            var rolUsuario = await _contexto.Roles.FirstOrDefaultAsync(r => r.Nombre == "Usuario");
            if (rolUsuario == null)
            {
                return (false, "No existe el rol base de usuario", null, null);
            }

            string codigoUnico;
            do
            {
                codigoUnico = Ayudantes.GeneradorCodigo.GenerarCodigoUsuario();
            } while (await _contexto.Usuarios.AnyAsync(u => u.CodigoUnico == codigoUnico));

            var usuario = new Models.Entidades.Usuario
            {
                CodigoUnico = codigoUnico,
                Nombre = modelo.Nombre,
                CorreoElectronico = modelo.CorreoElectronico,
                ContrasenaHash = null,
                FechaNacimiento = modelo.FechaNacimiento,
                Genero = modelo.Genero,
                RolId = rolUsuario.Id,
                CarreraId = modelo.CarreraId,
                GoogleId = modelo.GoogleId,
                FotoGoogleUrl = modelo.FotoGoogleUrl,
                CorreoVerificado = true,
                AceptoTerminos = modelo.AceptoTerminos,
                EstaActivo = true,
                FechaRegistro = DateTime.UtcNow
            };

            _contexto.Usuarios.Add(usuario);
            await _contexto.SaveChangesAsync();

            var nivelInicial = await _contexto.Niveles.FirstOrDefaultAsync(n => n.Numero == 0);
            if (nivelInicial != null)
            {
                _contexto.ProgresosUsuario.Add(new Models.Entidades.ProgresoUsuario
                {
                    UsuarioId = usuario.Id,
                    NivelActualId = nivelInicial.Id,
                    XpTotal = 0,
                    RachaActual = 0,
                    RachaMaxima = 0
                });
            }

            _contexto.ConfiguracionesPomodoro.Add(new Models.Entidades.ConfiguracionPomodoro
            {
                UsuarioId = usuario.Id,
                TiempoEstudioMin = 25,
                TiempoDescansoMin = 5,
                TiempoDescansoLargoMin = 15,
                CiclosAntesDescansoLargo = 4,
                SonidoActivo = true,
                FechaActualizacion = DateTime.UtcNow
            });

            await _contexto.SaveChangesAsync();

            var token = GenerarToken(usuario);
            var refreshToken = GenerarRefreshToken();
            var refreshHash = HashToken(refreshToken);

            _contexto.TokensRefresh.Add(new Models.Entidades.TokenRefresh
            {
                UsuarioId = usuario.Id,
                Token = refreshHash,
                ExpiraEn = DateTime.UtcNow.AddDays(ObtenerExpiracionRefreshDias())
            });

            await _contexto.SaveChangesAsync();

            return (true, "Registro exitoso", token, refreshToken);
        }
    }
}
