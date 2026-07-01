using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace EpycusApp.Migrations
{
    /// <inheritdoc />
    public partial class AgregarIndiceUnicoEstadoAnimo : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            // Antes de crear el indice unico, eliminar duplicados historicos que puedan existir
            // (RegistrarEstadoAnimo insertaba una fila nueva cada vez, sin upsert, hasta que se
            // corrigio). Se conserva por cada (UsuarioId, Fecha) la fila con el Id mas alto,
            // que corresponde al registro mas reciente (autoincremental). Si no hay duplicados,
            // esta sentencia no borra nada.
            migrationBuilder.Sql(@"
                DELETE e1 FROM EstadosAnimo e1
                INNER JOIN (
                    SELECT UsuarioId, Fecha, MAX(Id) AS MaxId
                    FROM EstadosAnimo
                    GROUP BY UsuarioId, Fecha
                ) keep ON e1.UsuarioId = keep.UsuarioId AND e1.Fecha = keep.Fecha
                WHERE e1.Id <> keep.MaxId;
            ");

            migrationBuilder.CreateIndex(
                name: "IX_EstadosAnimo_UsuarioId_Fecha",
                table: "EstadosAnimo",
                columns: new[] { "UsuarioId", "Fecha" },
                unique: true);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropIndex(
                name: "IX_EstadosAnimo_UsuarioId_Fecha",
                table: "EstadosAnimo");
        }
    }
}
