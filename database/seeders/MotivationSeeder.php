<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Motivation\Infrastructure\Models\MotivationalQuoteModel;
use App\Modules\Motivation\Infrastructure\Models\UsageTipModel;
use Illuminate\Database\Seeder;

final class MotivationSeeder extends Seeder
{
    public function run(): void
    {
        $quotes = [
            [
                'text' => 'Aunque no seamos personas muy brillantes, con perseverancia y dedicación conseguiremos todo lo que nos propongamos.',
                'author' => 'Santiago Ramón y Cajal',
                'is_verified' => true,
            ],
            [
                'text' => 'Todo ser humano, si se lo propone, puede ser escultor de su propio cerebro.',
                'author' => 'Santiago Ramón y Cajal',
                'is_verified' => true,
            ],
            [
                'text' => 'Saber más es ser más libre.',
                'author' => 'César Vallejo',
                'is_verified' => true,
            ],
            [
                'text' => 'Enseñar exige respeto a los saberes de los educandos.',
                'author' => 'Paulo Freire',
                'is_verified' => true,
            ],
            [
                'text' => 'La educación es el arma más poderosa que puedes usar para cambiar el mundo.',
                'author' => 'Nelson Mandela',
                'is_verified' => true,
            ],
            [
                'text' => 'En la vida, nada es para temer, todo es para ser comprendido.',
                'author' => 'Marie Curie',
                'is_verified' => true,
            ],
            [
                'text' => 'Nunca consideres el estudio como una obligación, sino como una oportunidad para penetrar en el bello mundo del saber.',
                'author' => 'Albert Einstein',
                'is_verified' => false,
            ],
            [
                'text' => 'No fracasé, solo descubrí formas que no funcionan.',
                'author' => 'Thomas Edison',
                'is_verified' => false,
            ],
            [
                'text' => 'El que tiene un porqué para vivir puede soportar casi cualquier cómo.',
                'author' => 'Viktor Frankl',
                'is_verified' => true,
            ],
            [
                'text' => 'No hay camino para el aprendizaje, el aprendizaje es el camino.',
                'author' => 'Proverbio oriental',
                'is_verified' => false,
            ],
        ];

        foreach ($quotes as $q) {
            MotivationalQuoteModel::firstOrCreate(
                ['text' => $q['text']],
                ['author' => $q['author'], 'is_verified' => $q['is_verified']]
            );
        }

        $tips = [
            ['module_key' => 'habits', 'content' => 'Empieza con 2 o 3 hábitos. Diez hábitos abandonados a la semana desmotivan más que tres sostenidos todo el mes.'],
            ['module_key' => 'habits', 'content' => 'Si un hábito lleva varios días sin marcarse, quizás es momento de ajustarlo, no de forzarlo.'],
            ['module_key' => 'pomodoro', 'content' => 'Si te cuesta concentrarte al inicio, prueba sesiones de 15 minutos antes de saltar a 25.'],
            ['module_key' => 'pomodoro', 'content' => 'Vincula el Pomodoro a una misión concreta: enfocarte en algo específico rinde más que "estudiar en general".'],
            ['module_key' => 'missions', 'content' => 'Si una tarea te parece enorme, divide en subtareas de 20 a 30 minutos cada una.'],
            ['module_key' => 'missions', 'content' => 'Registra la fecha límite real, no una fecha optimista. El sistema mide mejor con datos honestos.'],
            ['module_key' => 'wellbeing', 'content' => 'No hace falta escribir mucho. Registrar solo el emoji ya sirve para ver tu patrón en el mes.'],
            ['module_key' => 'wellbeing', 'content' => 'Si notas varios días seguidos con ánimo bajo, revisa la sección de apoyo en Ajustes.'],
            ['module_key' => 'ranking', 'content' => 'El ranking es solo un dato más. Tu propio progreso frente a ti mismo importa más que tu posición.'],
            ['module_key' => 'ai_assistant', 'content' => 'Cuéntale contexto específico ("tengo examen el viernes de Cálculo") en vez de preguntas generales.'],
            ['module_key' => 'study_groups', 'content' => 'Estudiar acompañado ayuda a sostener el enfoque, pero elige compañeros con una meta similar a la tuya.'],
            ['module_key' => 'villains', 'content' => 'Fíjate en qué villano te tocó: suele coincidir con tu obstáculo más frecuente. Atácalo con esa información.'],
        ];

        foreach ($tips as $t) {
            UsageTipModel::firstOrCreate(
                ['module_key' => $t['module_key'], 'content' => $t['content']]
            );
        }
    }
}
