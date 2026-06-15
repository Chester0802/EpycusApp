using Microsoft.AspNetCore.Mvc.ModelBinding.Validation;
using System.ComponentModel.DataAnnotations;

namespace EpycusApp.Ayudantes;

/// <summary>
/// Valida que un checkbox booleano estÃ© marcado como true.
/// Reemplaza [Range(typeof(bool), "true", "true")] que falla en validaciÃ³n
/// cliente por incompatibilidad entre "True" (.NET) y "true" (HTML/JS).
/// </summary>
[AttributeUsage(AttributeTargets.Property, AllowMultiple = false)]
public class DebeSerVerdaderoAttribute : ValidationAttribute, IClientModelValidator
{
    public DebeSerVerdaderoAttribute()
    {
        ErrorMessage = "Debe aceptar los tÃ©rminos";
    }

    public override bool IsValid(object? value)
        => value is bool b && b;

    public void AddValidation(ClientModelValidationContext context)
    {
        context.Attributes.TryAdd("data-val", "true");
        context.Attributes.TryAdd("data-val-debeserverdadero", ErrorMessage ?? "Requerido");
    }
}
