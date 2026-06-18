using Microsoft.AspNetCore.Mvc.ModelBinding.Validation;
using System.ComponentModel.DataAnnotations;

namespace EpycusApp.Ayudantes;

/// <summary>
/// Valida que un checkbox booleano esté marcado como true.
/// Reemplaza [Range(typeof(bool), "true", "true")] que falla en validación
/// cliente por incompatibilidad entre "True" (.NET) y "true" (HTML/JS).
/// </summary>
[AttributeUsage(AttributeTargets.Property, AllowMultiple = false)]
public class DebeSerVerdaderoAttribute : ValidationAttribute, IClientModelValidator
{
    public DebeSerVerdaderoAttribute()
    {
        ErrorMessage = "Debe aceptar los términos";
    }

    public override bool IsValid(object? value)
        => value is bool b && b;

    public void AddValidation(ClientModelValidationContext context)
    {
        context.Attributes.TryAdd("data-val", "true");
        context.Attributes.TryAdd("data-val-debeserverdadero", ErrorMessage ?? "Requerido");
    }
}
