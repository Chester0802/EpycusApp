using System;
using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels
{
    public class RegistroViewModel
    {
        [Required(ErrorMessage = "El nombre es obligatorio")]
        [StringLength(150, ErrorMessage = "El nombre no puede exceder 150 caracteres")]
        public string Nombre { get; set; } = string.Empty;

        [Required(ErrorMessage = "El correo electrónico es requerido")]
        [EmailAddress(ErrorMessage = "El formato del correo no es válido")]
        public string CorreoElectronico { get; set; } = string.Empty;

        [Required(ErrorMessage = "La contraseña es requerida")]
        [RegularExpression("^(?=.*[a-zñ])(?=.*[A-ZÑ])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$", ErrorMessage = "La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.")]
        [DataType(DataType.Password)]
        public string Contrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "Confirma la contraseña")]
        [Compare(nameof(Contrasena), ErrorMessage = "Las contraseñas no coinciden")]
        [DataType(DataType.Password)]
        public string ConfirmarContrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "La fecha de nacimiento es obligatoria")]
        [DataType(DataType.Date)]
        public DateOnly FechaNacimiento { get; set; }

        [Required(ErrorMessage = "Seleccione un género")]
        public string Genero { get; set; } = string.Empty;

        [Required(ErrorMessage = "Seleccione una carrera")]
        public int CarreraId { get; set; }

        public bool AceptoTerminos { get; set; }
    }
}
