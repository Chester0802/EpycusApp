using System;
using System.ComponentModel.DataAnnotations;

namespace EpycusApp.ViewModels
{
    public class RegistroViewModel
    {
        [Required(ErrorMessage = "El nombre es obligatorio")]
        [StringLength(150, ErrorMessage = "El nombre no puede exceder 150 caracteres")]
        public string Nombre { get; set; } = string.Empty;

        [Required(ErrorMessage = "El correo electrÃ³nico es requerido")]
        [EmailAddress(ErrorMessage = "El formato del correo no es vÃ¡lido")]
        public string CorreoElectronico { get; set; } = string.Empty;

        [Required(ErrorMessage = "La contraseÃ±a es requerida")]
        [RegularExpression("^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[!@#$%^&*]).{8,}$", ErrorMessage = "La contraseÃ±a debe tener mÃ­nimo 8 caracteres, una mayÃºscula, una minÃºscula, un nÃºmero y un carÃ¡cter especial.")]
        [DataType(DataType.Password)]
        public string Contrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "Confirma la contraseÃ±a")]
        [Compare(nameof(Contrasena), ErrorMessage = "Las contraseÃ±as no coinciden")]
        [DataType(DataType.Password)]
        public string ConfirmarContrasena { get; set; } = string.Empty;

        [Required(ErrorMessage = "La fecha de nacimiento es obligatoria")]
        [DataType(DataType.Date)]
        public DateTime FechaNacimiento { get; set; }

        [Required(ErrorMessage = "Seleccione un gÃ©nero")]
        public string Genero { get; set; } = string.Empty;

        [Required(ErrorMessage = "Seleccione una carrera")]
        public int CarreraId { get; set; }

        [Range(typeof(bool), "true", "true", ErrorMessage = "Debe aceptar los tÃ©rminos")]
        public bool AceptoTerminos { get; set; }
    }
}
