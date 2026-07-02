using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace EpycusApp.Migrations
{
    /// <inheritdoc />
    public partial class AgregarIndicesYUnicidadSesionPomodoro : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // Antes de crear el indice unico sobre la columna calculada, cerrar sesiones
            // "abandonadas" duplicadas que puedan existir de la condicion de carrera de
            // IniciarSesionSiNoActiva (check-then-insert sin bloqueo, ver Pomodoro.md punto
            // 2.1): si un usuario tiene mas de una sesion sin FechaFin, se conserva la mas
            // reciente (Id mas alto) como la "activa" y se cancelan las demas (FechaFin=ahora,
            // FueCompletada=0), igual que ya se hizo para EstadosAnimo en
            // AgregarIndiceUnicoEstadoAnimo. Si no hay duplicados, esta sentencia no cambia
            // ninguna fila.
            migrationBuilder.Sql(@"
                UPDATE SesionesPomodoro s1
                INNER JOIN (
                    SELECT UsuarioId, MAX(Id) AS MaxId
                    FROM SesionesPomodoro
                    WHERE FechaFin IS NULL
                    GROUP BY UsuarioId
                ) keep ON s1.UsuarioId = keep.UsuarioId
                SET s1.FechaFin = UTC_TIMESTAMP(), s1.FueCompletada = 0
                WHERE s1.FechaFin IS NULL AND s1.Id <> keep.MaxId;
            ");

            migrationBuilder.AddColumn<int>(
                name: "SesionAbiertaMarcador",
                table: "SesionesPomodoro",
                type: "int",
                nullable: true,
                computedColumnSql: "(CASE WHEN `FechaFin` IS NULL THEN `UsuarioId` ELSE NULL END)",
                stored: true);

            migrationBuilder.CreateIndex(
                name: "IX_SesionesPomodoro_SesionAbiertaMarcador",
                table: "SesionesPomodoro",
                column: "SesionAbiertaMarcador",
                unique: true);

            migrationBuilder.CreateIndex(
                name: "IX_SesionesPomodoro_UsuarioId_FechaInicio",
                table: "SesionesPomodoro",
                columns: new[] { "UsuarioId", "FechaInicio" });
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropIndex(
                name: "IX_SesionesPomodoro_SesionAbiertaMarcador",
                table: "SesionesPomodoro");

            migrationBuilder.DropIndex(
                name: "IX_SesionesPomodoro_UsuarioId_FechaInicio",
                table: "SesionesPomodoro");

            migrationBuilder.DropColumn(
                name: "SesionAbiertaMarcador",
                table: "SesionesPomodoro");
        }
    }
}
