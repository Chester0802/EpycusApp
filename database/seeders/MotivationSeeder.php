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
                'text' => 'En la vida no hay cosas que temer, solo cosas que comprender. Ahora es el momento de comprender más para temer menos.',
                'author' => 'Marie Curie',
                'is_verified' => true,
            ],
            [
                'text' => 'Primero hay que tener curiosidad. Lo importante es no dejar de hacerse preguntas.',
                'author' => 'Albert Einstein',
                'is_verified' => true,
            ],
            [
                'text' => 'Nunca consideres el estudio como una obligación, sino como una oportunidad para penetrar en el bello mundo del saber.',
                'author' => 'Albert Einstein',
                'is_verified' => true,
            ],
            [
                'text' => 'No importa cuán despacio vayas, siempre y cuando no te detengas.',
                'author' => 'Confucio',
                'is_verified' => true,
            ],
            [
                'text' => 'El principio primordial es no engañarte a ti mismo, y tú eres la persona más fácil de engañar.',
                'author' => 'Richard Feynman',
                'is_verified' => true,
            ],
            [
                'text' => 'Estudia duro lo que más te interese de la forma más indisciplinada, irreverente y original posible.',
                'author' => 'Richard Feynman',
                'is_verified' => true,
            ],
            [
                'text' => 'El presente es de ellos; el futuro, por el que realmente he trabajado, es mío.',
                'author' => 'Nikola Tesla',
                'is_verified' => true,
            ],
            [
                'text' => 'En algún lugar, algo increíble está esperando ser descubierto.',
                'author' => 'Carl Sagan',
                'is_verified' => true,
            ],
            [
                'text' => 'Ese cerebro mío es algo más que meramente mortal, como el tiempo lo demostrará.',
                'author' => 'Ada Lovelace',
                'is_verified' => true,
            ],
            [
                'text' => 'La suerte favorece únicamente a la mente preparada.',
                'author' => 'Louis Pasteur',
                'is_verified' => true,
            ],
            [
                'text' => 'Si he visto más lejos, es poniéndome sobre los hombros de gigantes.',
                'author' => 'Isaac Newton',
                'is_verified' => true,
            ],
            [
                'text' => 'Recuerda mirar hacia arriba, a las estrellas, y no hacia abajo, a tus pies.',
                'author' => 'Stephen Hawking',
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
                'text' => 'El que tiene un porqué para vivir puede soportar casi cualquier cómo.',
                'author' => 'Viktor Frankl',
                'is_verified' => true,
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
            ['module_key' => 'missions', 'content' => 'Enfócate en el Cuadrante Q2 (Planificar): dedicar tiempo a tareas importantes antes de que sean urgentes es el mayor secreto contra la procrastinación.'],
            ['module_key' => 'missions', 'content' => 'En el tablero Kanban, mueve tus misiones a "En Proceso" y marca sus subtareas mientras estudias para ver tu avance visual.'],
            ['module_key' => 'missions', 'content' => 'Cuando completes todos los pasos de una misión, pásala a "En Revisión" para darle un último chequeo antes de reclamar tu XP.'],
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
