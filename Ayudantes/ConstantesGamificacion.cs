namespace EPYCUS_WEB_v0._1.Ayudantes
{
    /// <summary>
    /// Constantes para el sistema de gamificación
    /// </summary>
    public static class ConstantesGamificacion
    {
        /// <summary>
        /// Experiencia base otorgada al completar un hábito
        /// </summary>
        public const int XP_BASE_HABITO = 20;

        /// <summary>
        /// Bonus de experiencia por racha de 7 días
        /// </summary>
        public const int XP_BONUS_RACHA_SEMANAL = 10;

        /// <summary>
        /// Días necesarios para obtener bonus de racha
        /// </summary>
        public const int DIAS_RACHA_BONUS = 7;

        /// <summary>
        /// Experiencia base otorgada al completar una misión
        /// </summary>
        public const int XP_BASE_MISION = 50;

        /// <summary>
        /// Experiencia otorgada por completar una sesión Pomodoro
        /// </summary>
        public const int XP_BASE_POMODORO = 15;
    }
}
