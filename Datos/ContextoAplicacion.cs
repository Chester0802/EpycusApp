using EpycusApp.Models.Entidades;
using Microsoft.EntityFrameworkCore;

namespace EpycusApp.Datos
{
    public class ContextoAplicacion : DbContext
    {
        public ContextoAplicacion(DbContextOptions<ContextoAplicacion> options)
            : base(options)
        {
        }

        public DbSet<Usuario> Usuarios { get; set; } = null!;
        public DbSet<Rol> Roles { get; set; } = null!;
        public DbSet<Carrera> Carreras { get; set; } = null!;
        public DbSet<Nivel> Niveles { get; set; } = null!;
        public DbSet<ProgresoUsuario> ProgresosUsuario { get; set; } = null!;
        public DbSet<Personaje> Personajes { get; set; } = null!;
        public DbSet<ImagenNivelPersonaje> ImagenesNivelPersonaje { get; set; } = null!;
        public DbSet<PersonajeUsuario> PersonajesUsuario { get; set; } = null!;
        public DbSet<Logro> Logros { get; set; } = null!;
        public DbSet<LogroUsuario> LogrosUsuario { get; set; } = null!;
        public DbSet<Categoria> Categorias { get; set; } = null!;
        public DbSet<Habito> Habitos { get; set; } = null!;
        public DbSet<RegistroHabito> RegistrosHabito { get; set; } = null!;
        public DbSet<ConfiguracionPomodoro> ConfiguracionesPomodoro { get; set; } = null!;
        public DbSet<SesionPomodoro> SesionesPomodoro { get; set; } = null!;
        public DbSet<Mision> Misiones { get; set; } = null!;
        public DbSet<EstadoAnimo> EstadosAnimo { get; set; } = null!;
        public DbSet<FraseMotivacional> FrasesMotivacionales { get; set; } = null!;
        public DbSet<TipPomodoro> TipsPomodoro { get; set; } = null!;
        public DbSet<Tema> Temas { get; set; } = null!;
        public DbSet<TemaUsuario> TemasUsuario { get; set; } = null!;
        public DbSet<Suscripcion> Suscripciones { get; set; } = null!;
        public DbSet<TokenRefresh> TokensRefresh { get; set; } = null!;
        public DbSet<VerificacionCorreo> VerificacionesCorreo { get; set; } = null!;
        public DbSet<RecuperacionContrasena> RecuperacionesContrasena { get; set; } = null!;
        public DbSet<DiasSemanaHabito> DiasSemanaHabito { get; set; }
 = null!;
        public DbSet<MensajeIA> MensajesIA { get; set; } = null!;
        public DbSet<EntradaDiario> EntradasDiario { get; set; } = null!;
        public DbSet<SubTarea> SubTareas { get; set; } = null!;

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            modelBuilder.Entity<Usuario>()
                .HasIndex(u => u.CorreoElectronico)
                .IsUnique();

            modelBuilder.Entity<Usuario>()
                .HasIndex(u => u.CodigoUnico)
                .IsUnique();

            modelBuilder.Entity<Usuario>()
                .HasIndex(u => u.GoogleId)
                .IsUnique();

            modelBuilder.Entity<Usuario>()
                .HasOne(u => u.Rol)
                .WithMany()
                .HasForeignKey(u => u.RolId)
                .OnDelete(DeleteBehavior.Restrict);

            modelBuilder.Entity<Usuario>()
                .HasOne(u => u.Carrera)
                .WithMany(c => c.Usuarios)
                .HasForeignKey(u => u.CarreraId)
                .OnDelete(DeleteBehavior.Restrict);

            modelBuilder.Entity<Usuario>()
                .HasOne(u => u.TemaActual)
                .WithMany()
                .HasForeignKey(u => u.TemaActualId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            modelBuilder.Entity<ProgresoUsuario>()
                .HasIndex(p => p.UsuarioId)
                .IsUnique();

            modelBuilder.Entity<ProgresoUsuario>()
                .HasOne(p => p.Usuario)
                .WithOne(u => u.Progreso)
                .HasForeignKey<ProgresoUsuario>(p => p.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<ProgresoUsuario>()
                .HasOne(p => p.NivelActual)
                .WithMany(n => n.Progresos)
                .HasForeignKey(p => p.NivelActualId)
                .OnDelete(DeleteBehavior.Restrict);

            modelBuilder.Entity<Habito>()
                .HasOne(h => h.Usuario)
                .WithMany(u => u.Habitos)
                .HasForeignKey(h => h.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<Habito>()
                .HasOne(h => h.Categoria)
                .WithMany(c => c.Habitos)
                .HasForeignKey(h => h.CategoriaId)
                .OnDelete(DeleteBehavior.Restrict);

            modelBuilder.Entity<Habito>()
                .HasMany(h => h.DiasSemana)
                .WithOne(d => d.Habito)
                .HasForeignKey(d => d.HabitoId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<RegistroHabito>()
                .HasOne(r => r.Habito)
                .WithMany(h => h.Registros)
                .HasForeignKey(r => r.HabitoId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<SesionPomodoro>()
                .HasOne(s => s.Usuario)
                .WithMany(u => u.SesionesPomodoro)
                .HasForeignKey(s => s.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<SesionPomodoro>()
                .HasOne(s => s.Habito)
                .WithMany(h => h.SesionesPomodoro)
                .HasForeignKey(s => s.HabitoId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            modelBuilder.Entity<SesionPomodoro>()
                .HasOne(s => s.Mision)
                .WithMany(m => m.SesionesPomodoro)
                .HasForeignKey(s => s.MisionId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            modelBuilder.Entity<Mision>()
                .HasOne(m => m.Usuario)
                .WithMany(u => u.Misiones)
                .HasForeignKey(m => m.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<Mision>()
                .HasOne(m => m.Categoria)
                .WithMany(c => c.Misiones)
                .HasForeignKey(m => m.CategoriaId)
                .OnDelete(DeleteBehavior.Restrict);

            modelBuilder.Entity<Personaje>()
                .HasOne(p => p.Carrera)
                .WithMany()
                .HasForeignKey(p => p.CarreraId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            modelBuilder.Entity<ImagenNivelPersonaje>()
                .HasOne(i => i.Personaje)
                .WithMany(p => p.Imagenes)
                .HasForeignKey(i => i.PersonajeId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<Suscripcion>()
                .HasOne(s => s.Usuario)
                .WithMany(u => u.Suscripciones)
                .HasForeignKey(s => s.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<Suscripcion>()
                .HasOne<Usuario>()
                .WithMany()
                .HasForeignKey(s => s.ActivadaPorAdminId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            // ── Índices para rendimiento ────────────────────────────────
            modelBuilder.Entity<MensajeIA>()
                .HasIndex(m => m.ConversacionId);

            modelBuilder.Entity<MensajeIA>()
                .HasIndex(m => m.UsuarioId);

            modelBuilder.Entity<Log>()
                .HasIndex(l => l.UsuarioId);

            modelBuilder.Entity<EstadoAnimo>()
                .HasIndex(e => e.UsuarioId);

            modelBuilder.Entity<EntradaDiario>()
                .HasIndex(e => new { e.UsuarioId, e.Fecha })
                .IsUnique();

            modelBuilder.Entity<EntradaDiario>()
                .HasIndex(e => e.UsuarioId);

            modelBuilder.Entity<EntradaDiario>()
                .HasOne(e => e.Usuario)
                .WithMany()
                .HasForeignKey(e => e.UsuarioId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<TokenRefresh>()
                .HasIndex(t => t.UsuarioId);

            modelBuilder.Entity<TokenRefresh>()
                .HasIndex(t => t.Token);

            modelBuilder.Entity<RecuperacionContrasena>()
                .HasIndex(r => r.UsuarioId);

            modelBuilder.Entity<SubTarea>()
                .HasOne(st => st.Mision)
                .WithMany(m => m.SubTareas)
                .HasForeignKey(st => st.MisionId)
                .OnDelete(DeleteBehavior.Cascade);

            modelBuilder.Entity<SesionPomodoro>()
                .HasOne(s => s.SubTarea)
                .WithMany(st => st.SesionesPomodoro)
                .HasForeignKey(s => s.SubTareaId)
                .OnDelete(DeleteBehavior.SetNull)
                .IsRequired(false);

            modelBuilder.Entity<SubTarea>()
                .HasIndex(st => st.MisionId);

            modelBuilder.Entity<SesionPomodoro>()
                .HasIndex(s => s.SubTareaId);

            modelBuilder.Entity<SesionPomodoro>()
                .HasIndex(s => s.UsuarioId);

            modelBuilder.Entity<LogroUsuario>()
                .HasIndex(l => l.UsuarioId);

            modelBuilder.Entity<VerificacionCorreo>()
                .HasIndex(v => v.UsuarioId);
        }
    }
}
