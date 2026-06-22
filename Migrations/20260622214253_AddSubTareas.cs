using System;
using Microsoft.EntityFrameworkCore.Metadata;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace EpycusApp.Migrations
{
    /// <inheritdoc />
    public partial class AddSubTareas : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<int>(
                name: "SubTareaId",
                table: "SesionesPomodoro",
                type: "int",
                nullable: true);

            migrationBuilder.CreateTable(
                name: "SubTareas",
                columns: table => new
                {
                    Id = table.Column<int>(type: "int", nullable: false)
                        .Annotation("MySql:ValueGenerationStrategy", MySqlValueGenerationStrategy.IdentityColumn),
                    Nombre = table.Column<string>(type: "longtext", nullable: false)
                        .Annotation("MySql:CharSet", "utf8mb4"),
                    Descripcion = table.Column<string>(type: "longtext", nullable: true)
                        .Annotation("MySql:CharSet", "utf8mb4"),
                    EstaCompletada = table.Column<bool>(type: "tinyint(1)", nullable: false),
                    Orden = table.Column<int>(type: "int", nullable: false),
                    TiempoEnfoqueSegundos = table.Column<int>(type: "int", nullable: false),
                    FechaCreacion = table.Column<DateTime>(type: "datetime(6)", nullable: false),
                    FechaCompletado = table.Column<DateTime>(type: "datetime(6)", nullable: true),
                    MisionId = table.Column<int>(type: "int", nullable: false)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_SubTareas", x => x.Id);
                    table.ForeignKey(
                        name: "FK_SubTareas_Misiones_MisionId",
                        column: x => x.MisionId,
                        principalTable: "Misiones",
                        principalColumn: "Id",
                        onDelete: ReferentialAction.Cascade);
                })
                .Annotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.CreateIndex(
                name: "IX_SesionesPomodoro_SubTareaId",
                table: "SesionesPomodoro",
                column: "SubTareaId");

            migrationBuilder.CreateIndex(
                name: "IX_SubTareas_MisionId",
                table: "SubTareas",
                column: "MisionId");

            migrationBuilder.AddForeignKey(
                name: "FK_SesionesPomodoro_SubTareas_SubTareaId",
                table: "SesionesPomodoro",
                column: "SubTareaId",
                principalTable: "SubTareas",
                principalColumn: "Id",
                onDelete: ReferentialAction.SetNull);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_SesionesPomodoro_SubTareas_SubTareaId",
                table: "SesionesPomodoro");

            migrationBuilder.DropTable(
                name: "SubTareas");

            migrationBuilder.DropIndex(
                name: "IX_SesionesPomodoro_SubTareaId",
                table: "SesionesPomodoro");

            migrationBuilder.DropColumn(
                name: "SubTareaId",
                table: "SesionesPomodoro");
        }
    }
}
