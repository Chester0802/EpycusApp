<?php

declare(strict_types=1);

namespace Tests\Feature\Courses;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\FlashcardModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FlashcardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_flashcards(): void
    {
        $user = UserModel::factory()->create();
        $course = CourseModel::create([
            'user_id' => $user->id,
            'name' => 'Estructura de Datos',
            'color' => '#3b82f6',
        ]);

        $this->actingAs($user);

        // Crear flashcard manual
        $response = $this->postJson(route('courses.flashcards.store', ['course' => $course->id]), [
            'question' => '¿Qué es un Árbol AVL?',
            'answer' => 'Es un árbol binario de búsqueda auto-balanceable.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('flashcards', [
            'course_id' => $course->id,
            'question' => '¿Qué es un Árbol AVL?',
            'leitner_box' => 1,
        ]);

        // Listar flashcards
        $listResponse = $this->getJson(route('courses.flashcards.index', ['course' => $course->id]));
        $listResponse->assertOk()
            ->assertJsonPath('stats.total', 1)
            ->assertJsonPath('stats.box_counts.1', 1);
    }

    public function test_leitner_box_advances_and_recedes_correctly(): void
    {
        $user = UserModel::factory()->create();
        $course = CourseModel::create([
            'user_id' => $user->id,
            'name' => 'Física II',
            'color' => '#10b981',
        ]);

        $card = FlashcardModel::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'question' => 'Ley de Gauss',
            'answer' => 'El flujo del campo eléctrico...',
            'leitner_box' => 1,
            'next_review_at' => Carbon::now('America/Lima')->toDateString(),
        ]);

        $this->actingAs($user);

        // 1. Acierto ('good') -> Debe pasar a Caja 2 con +3 días
        $res = $this->postJson(route('flashcards.review', ['id' => $card->id]), [
            'rating' => 'good',
        ]);

        $res->assertOk();
        $card->refresh();
        $this->assertEquals(2, $card->leitner_box);
        $this->assertEquals(Carbon::now('America/Lima')->addDays(3)->toDateString(), $card->next_review_at->toDateString());

        // 2. Acierto ('easy') -> Debe pasar a Caja 3 con +7 días
        $this->postJson(route('flashcards.review', ['id' => $card->id]), ['rating' => 'easy']);
        $card->refresh();
        $this->assertEquals(3, $card->leitner_box);

        // 3. Fallo ('fail') en Caja 3 -> Debe retroceder a Caja 2
        $this->postJson(route('flashcards.review', ['id' => $card->id]), ['rating' => 'fail']);
        $card->refresh();
        $this->assertEquals(2, $card->leitner_box);
    }

    public function test_user_can_generate_and_evaluate_mock_exam(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*generativelanguage.googleapis.com*' => \Illuminate\Support\Facades\Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Simulacro Parcial: Redes de Computadoras',
                                        'time_limit_minutes' => 20,
                                        'multiple_choice' => [
                                            [
                                                'id' => 1,
                                                'question' => '¿Qué capa del modelo OSI gestiona el enrutamiento?',
                                                'options' => ['Capa 1', 'Capa 2', 'Capa 3', 'Capa 4'],
                                                'correct_index' => 2,
                                                'explanation' => 'La capa de red (Capa 3) gestiona el enrutamiento y direccionamiento lógico IP.',
                                            ],
                                        ],
                                        'open_questions' => [
                                            [
                                                'id' => 7,
                                                'question' => 'Explique el protocolo TCP.',
                                                'expected_keypoints' => 'Orientado a conexión, control de flujo y congestión.',
                                            ],
                                        ],
                                        'final_grade' => 18.0,
                                        'feedback_summary' => 'Buen desempeño.',
                                        'questions_review' => [
                                            ['id' => 1, 'is_correct' => true, 'score' => 2.0, 'max_score' => 2.0, 'comment' => 'Correcto'],
                                            ['id' => 7, 'is_correct' => true, 'score' => 2.0, 'max_score' => 2.0, 'comment' => 'Bien'],
                                        ],
                                        'failed_concepts' => [],
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
            '*openrouter.ai*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'title' => 'Simulacro Parcial: Redes de Computadoras',
                                'time_limit_minutes' => 20,
                                'multiple_choice' => [
                                    [
                                        'id' => 1,
                                        'question' => '¿Qué capa del modelo OSI gestiona el enrutamiento?',
                                        'options' => ['Capa 1', 'Capa 2', 'Capa 3', 'Capa 4'],
                                        'correct_index' => 2,
                                        'explanation' => 'La capa de red gestiona el enrutamiento.',
                                    ],
                                ],
                                'open_questions' => [
                                    [
                                        'id' => 7,
                                        'question' => 'Explique el protocolo TCP.',
                                        'expected_keypoints' => 'Orientado a conexión.',
                                    ],
                                ],
                                'final_grade' => 18.0,
                                'feedback_summary' => 'Buen desempeño.',
                                'questions_review' => [
                                    ['id' => 1, 'is_correct' => true, 'score' => 2.0, 'max_score' => 2.0, 'comment' => 'Correcto'],
                                    ['id' => 7, 'is_correct' => true, 'score' => 2.0, 'max_score' => 2.0, 'comment' => 'Bien'],
                                ],
                                'failed_concepts' => [],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = UserModel::factory()->create();
        $course = CourseModel::create([
            'user_id' => $user->id,
            'name' => 'Redes de Computadoras',
            'color' => '#6366f1',
        ]);

        $this->actingAs($user);

        // 1. Generar Simulacro
        $generateResponse = $this->postJson(route('courses.mock-exam.generate', ['course' => $course->id]));
        $generateResponse->assertOk()
            ->assertJsonStructure([
                'success',
                'exam' => [
                    'title',
                    'time_limit_minutes',
                    'multiple_choice',
                    'open_questions',
                ],
            ]);

        $examData = $generateResponse->json('exam');
        $this->assertNotEmpty($examData['multiple_choice']);
        $this->assertNotEmpty($examData['open_questions']);

        // 2. Evaluar Simulacro
        $evaluateResponse = $this->postJson(route('courses.mock-exam.evaluate', ['course' => $course->id]), [
            'exam' => $examData,
            'user_answers' => [
                'mc_1' => 0,
                'open_7' => 'Esta es una respuesta detallada con fundamentación técnica suficiente para el curso de redes.',
            ],
        ]);

        $evaluateResponse->assertOk()
            ->assertJsonStructure([
                'evaluation' => [
                    'final_grade',
                    'feedback_summary',
                    'questions_review',
                ],
                'autocreated_flashcards_count',
                'xp_awarded',
            ]);
    }
}

