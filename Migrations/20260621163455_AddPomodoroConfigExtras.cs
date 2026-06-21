using Microsoft.EntityFrameworkCore.Migrations;

#nullable disable

namespace EpycusApp.Migrations
{
    /// <inheritdoc />
    public partial class AddPomodoroConfigExtras : Migration
    {
        /// <inheritdoc />
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.AddColumn<bool>(
                name: "AutoIniciarDescanso",
                table: "ConfiguracionesPomodoro",
                type: "tinyint(1)",
                nullable: false,
                defaultValue: false);

            migrationBuilder.AddColumn<bool>(
                name: "AutoIniciarEnfoque",
                table: "ConfiguracionesPomodoro",
                type: "tinyint(1)",
                nullable: false,
                defaultValue: false);

            migrationBuilder.AddColumn<int>(
                name: "MetaDiariaCiclos",
                table: "ConfiguracionesPomodoro",
                type: "int",
                nullable: false,
                defaultValue: 0);

            migrationBuilder.AddColumn<int>(
                name: "ModoPersonalizadoMinutos",
                table: "ConfiguracionesPomodoro",
                type: "int",
                nullable: false,
                defaultValue: 25);

            migrationBuilder.AddColumn<bool>(
                name: "NotificacionDesktop",
                table: "ConfiguracionesPomodoro",
                type: "tinyint(1)",
                nullable: false,
                defaultValue: true);

            migrationBuilder.AddColumn<string>(
                name: "SonidoSeleccionado",
                table: "ConfiguracionesPomodoro",
                type: "longtext",
                nullable: false)
                .Annotation("MySql:CharSet", "utf8mb4");

            migrationBuilder.AddColumn<bool>(
                name: "TicTacActivo",
                table: "ConfiguracionesPomodoro",
                type: "tinyint(1)",
                nullable: false,
                defaultValue: false);

            migrationBuilder.AddColumn<bool>(
                name: "VibracionActiva",
                table: "ConfiguracionesPomodoro",
                type: "tinyint(1)",
                nullable: false,
                defaultValue: true);

            migrationBuilder.AddColumn<int>(
                name: "Volumen",
                table: "ConfiguracionesPomodoro",
                type: "int",
                nullable: false,
                defaultValue: 100);
        }

        /// <inheritdoc />
        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropColumn(
                name: "AutoIniciarDescanso",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "AutoIniciarEnfoque",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "MetaDiariaCiclos",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "ModoPersonalizadoMinutos",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "NotificacionDesktop",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "SonidoSeleccionado",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "TicTacActivo",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "VibracionActiva",
                table: "ConfiguracionesPomodoro");

            migrationBuilder.DropColumn(
                name: "Volumen",
                table: "ConfiguracionesPomodoro");
        }
    }
}
