<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Villains\Infrastructure\Models\VillainModel;
use Illuminate\Database\Seeder;

final class VillainsSeeder extends Seeder
{
    public function run(): void
    {
        $villains = [
            [
                'code' => 'procrastination',
                'name' => 'La Postergación',
                'description' => 'El arte de dejar todo para después acecha tu productividad y tranquilidad.',
                'weakness_description' => 'Se debilita cuando completas misiones y estudias en grupos.',
            ],
            [
                'code' => 'distraction',
                'name' => 'La Distracción',
                'description' => 'El zumbido del celular, reels y redes sociales te alejan del enfoque profundo.',
                'weakness_description' => 'Se debilita cuando completas bloques Pomodoro de estudio sin abandonar.',
            ],
            [
                'code' => 'anxiety',
                'name' => 'La Ansiedad',
                'description' => 'El agobio ante la carga de trabajo y los exámenes nubla tu claridad mental.',
                'weakness_description' => 'Se debilita cuando escribes en tu diario de bienestar y cumples hábitos.',
            ],
            [
                'code' => 'disorder',
                'name' => 'El Desorden',
                'description' => 'No saber por dónde empezar te paraliza y divide tu energía en mil cosas.',
                'weakness_description' => 'Se debilita cuando creas y avanzas misiones con subtareas desglosadas.',
            ],
            [
                'code' => 'fatigue',
                'name' => 'El Cansancio',
                'description' => 'Llegas agotado de clases y el rendimiento intelectual se desploma.',
                'weakness_description' => 'Se debilita cuando cumples hábitos de sueño, descanso y bienestar.',
            ],
            [
                'code' => 'impostor_syndrome',
                'name' => 'El Síndrome del Impostor',
                'description' => 'La duda constante sobre tu capacidad y el temor irracional a no estar a la altura.',
                'weakness_description' => 'Se debilita cuando registras tus reflexiones en el Diario y completas misiones.',
            ],
            [
                'code' => 'perfectionism',
                'name' => 'El Perfeccionismo Paralizante',
                'description' => 'La obsesión por un resultado impecable que bloquea el inicio de cualquier proyecto.',
                'weakness_description' => 'Se debilita con bloques Pomodoro ("hecho es mejor que perfecto") y misiones.',
            ],
            [
                'code' => 'isolation',
                'name' => 'El Aislamiento Académico',
                'description' => 'Quedarse atrapado en dudas complejas sin pedir ayuda ni compartir con compañeros.',
                'weakness_description' => 'Se debilita cuando participas en Grupos de Estudio y consultas a la IA Edy.',
            ],
            [
                'code' => 'burnout',
                'name' => 'La Sobrecarga (Burnout)',
                'description' => 'Estudiar sin pausas ni equilibrio hasta que el cerebro se satura y pierde memoria.',
                'weakness_description' => 'Se debilita cuando cumples hábitos de salud y respetas las pausas Pomodoro.',
            ],
            [
                'code' => 'all_nighter',
                'name' => 'La Ilusión de la Última Noche',
                'description' => 'La falsa creencia de que amanecerse antes del examen compensa el estudio regular.',
                'weakness_description' => 'Se debilita planificando misiones en Cuadrante Q2 y manteniendo hábitos diarios.',
            ],
        ];

        foreach ($villains as $v) {
            VillainModel::updateOrCreate(
                ['code' => $v['code']],
                $v
            );
        }
    }
}
