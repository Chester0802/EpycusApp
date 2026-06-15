using System.ComponentModel.DataAnnotations.Schema;

namespace EpycusApp.Models.Entidades
{
    [Table("DiasSemanaHabito")]
    public class DiasSemanaHabito
    {
        public int Id { get; set; }
        public int HabitoId { get; set; }
        public int DiaSemana { get; set; }

        [ForeignKey(nameof(HabitoId))]
        public Habito Habito { get; set; } = null!;
    }
}
