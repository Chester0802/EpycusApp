/*
 * Escalafón de Rangos Profesionales por Carrera Individual (Fases 1 a 10).
 *
 * Cada una de las 11 carreras posee 10 títulos evolutivos únicos que reflejan
 * el progreso académico y profesional real del estudiante en Epycus.
 */

export const CAREER_RANKS = {
    'Medicina': [
        'Preclínico',
        'Estudiante de Ciencias Médicas',
        'Externo de Medicina',
        'Interno de Medicina',
        'Médico Cirujano Junior',
        'Residente de Especialidad',
        'Médico Especialista',
        'Jefe de Servicio',
        'Director Médico',
        'Eminencia Médica',
    ],
    'Enfermería': [
        'Asistente Estudiantil',
        'Practicante de Cuidado',
        'Enfermero/a Junior',
        'Especialista de Sala',
        'Cuidados Intensivos',
        'Jefe/a de Enfermería',
        'Supervisor/a Hospitalario',
        'Gestor/a de Salud',
        'Director/a de Cuidados',
        'Master en Ciencias del Cuidado',
    ],
    'Obstetricia': [
        'Estudiante Obstetra',
        'Practicante Pre-profesional',
        'Obstetra Asistencial',
        'Especialista Materno-Infantil',
        'Asesor/a de Salud Sexual',
        'Jefe/a de Sala de Partos',
        'Supervisor/a Gineco-Obstétrica',
        'Director/a de Salud Reproductiva',
        'Master en Salud Maternal',
        'Eminencia de la Salud Maternal',
    ],
    'Administración de Empresas': [
        'Asistente de Gestión',
        'Analista Junior',
        'Coordinador de Operaciones',
        'Administrador de Proyectos',
        'Jefe de Área',
        'Gerente de Unidad',
        'Director Operativo',
        'Vicepresidente Ejecutivo',
        'Chief Executive Officer (CEO)',
        'Líder Corporativo Internacional',
    ],
    'Contabilidad': [
        'Auxiliar Contable',
        'Asistente Tributario',
        'Contador Junior',
        'Auditor de Operaciones',
        'Contador Senior',
        'Jefe de Finanzas',
        'Controller Financiero',
        'Auditor General',
        'Chief Financial Officer (CFO)',
        'Maestro en Estrategia Financiera',
    ],
    'Ingeniería Civil': [
        'Cadete de Obra',
        'Auxiliar de Campo',
        'Ingeniero Residente Jr.',
        'Diseñador Estructural',
        'Ingeniero Residente Sr.',
        'Inspector de Infraestructura',
        'Gerente de Proyecto',
        'Director de Grandes Obras',
        'Consultor Internacional',
        'Gran Arquitecto del Entorno',
    ],
    'Ingeniería Industrial': [
        'Analista de Procesos Jr.',
        'Asistente de Operaciones',
        'Ingeniero de Calidad',
        'Optimizado de Cadena de Suministro',
        'Jefe de Planta',
        'Gerente de Manufactura',
        'Director de Logística',
        'Vicepresidente de Operaciones',
        'Chief Operating Officer (COO)',
        'Maestro de la Eficiencia Global',
    ],
    'Ingeniería de Minas': [
        'Practicante de Mina',
        'Auxiliar de Geología',
        'Ingeniero de Minado Jr.',
        'Especialista en Geotecnia',
        'Jefe de Turno',
        'Superintendente de Mina',
        'Gerente de Operaciones Mineras',
        'Director de Complejos Mineros',
        'Consultor Minero Global',
        'Comandante de Recursos del Subsuelo',
    ],
    'Arquitectura': [
        'Dibujante Técnico',
        'Proyectista Jr.',
        'Arquitecto Diseñador',
        'Coordinador BIM',
        'Arquitecto Principal',
        'Director de Taller',
        'Gestor Urbano',
        'Maestro de la Forma y Espacio',
        'Arquitecto Visionario',
        'Gran Diseñador de Entornos',
    ],
    'Ingeniería de Sistemas': [
        'Scripting Padawan',
        'Junior Developer',
        'Full Stack Engineer',
        'DevOps Specialist',
        'Senior Software Architect',
        'Tech Lead',
        'VP of Engineering',
        'Chief Technology Officer (CTO)',
        'System Visionary',
        'Grandmaster Cyber Architect',
    ],
    'Derecho': [
        'Practicante Legal',
        'Pasante de Bufete',
        'Abogado Junior',
        'Consultor Jurídico',
        'Abogado Litigante',
        'Socio de Firma',
        'Juez de Jurisdicción',
        'Fiscal Superior',
        'Magistrado',
        'Jurista Supremo de la Ley',
    ],
};

const DEFAULT_RANKS = [
    'Estudiante Inicial',
    'Aprendiz Dedicado',
    'Practicante',
    'Especialista Junior',
    'Profesional',
    'Especialista Senior',
    'Líder de Proyecto',
    'Director de Área',
    'Maestro de Disciplina',
    'Líder Absoluto',
];

/**
 * Obtiene el título profesional según la carrera exacta y la fase (1-10).
 *
 * @param {string|null} career Nombre exacto de la carrera
 * @param {number} phase Número de fase (1 a 10)
 * @returns {string} Título profesional evolutivo
 */
export function getCareerRankTitle(career, phase = 1) {
    const validPhase = Math.min(Math.max(Number(phase) || 1, 1), 10);
    const ranks = CAREER_RANKS[career] || DEFAULT_RANKS;
    return ranks[validPhase - 1] || ranks[0];
}
