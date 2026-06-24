using System;
using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace EpycusApp.Migrations
{
    /// <inheritdoc />
    public partial class AgregarBloqueoIntentosFallidos : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_Log_Usuarios_UsuarioId",
                table: "Log");

            migrationBuilder.DropPrimaryKey(
                name: "PK_Log",
                table: "Log");

            migrationBuilder.RenameTable(
                name: "Log",
                newName: "Logs");

            migrationBuilder.RenameIndex(
                name: "IX_Log_UsuarioId",
                table: "Logs",
                newName: "IX_Logs_UsuarioId");

            migrationBuilder.AddColumn<DateTime>(
                name: "BloqueoHasta",
                table: "Usuarios",
                type: "datetime(6)",
                nullable: true);

            migrationBuilder.AddColumn<int>(
                name: "IntentosFallidos",
                table: "Usuarios",
                type: "int",
                nullable: false,
                defaultValue: 0);

            migrationBuilder.AddPrimaryKey(
                name: "PK_Logs",
                table: "Logs",
                column: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_Logs_Usuarios_UsuarioId",
                table: "Logs",
                column: "UsuarioId",
                principalTable: "Usuarios",
                principalColumn: "Id");
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropForeignKey(
                name: "FK_Logs_Usuarios_UsuarioId",
                table: "Logs");

            migrationBuilder.DropPrimaryKey(
                name: "PK_Logs",
                table: "Logs");

            migrationBuilder.DropColumn(
                name: "BloqueoHasta",
                table: "Usuarios");

            migrationBuilder.DropColumn(
                name: "IntentosFallidos",
                table: "Usuarios");

            migrationBuilder.RenameTable(
                name: "Logs",
                newName: "Log");

            migrationBuilder.RenameIndex(
                name: "IX_Logs_UsuarioId",
                table: "Log",
                newName: "IX_Log_UsuarioId");

            migrationBuilder.AddPrimaryKey(
                name: "PK_Log",
                table: "Log",
                column: "Id");

            migrationBuilder.AddForeignKey(
                name: "FK_Log_Usuarios_UsuarioId",
                table: "Log",
                column: "UsuarioId",
                principalTable: "Usuarios",
                principalColumn: "Id");
        }
    }
}
