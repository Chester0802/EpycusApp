<?php

namespace Tests\Feature;

use App\Modules\Identity\Infrastructure\Models\UserModel as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account_for_regular_user(): void
    {
        $user = User::factory()->create([
            'google_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_google_user_can_delete_account_without_password(): void
    {
        $user = User::factory()->create([
            'google_id' => '109876543210987654321',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_google_user_cannot_update_password(): void
    {
        $user = User::factory()->create([
            'google_id' => '109876543210987654321',
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHas('error');
    }

    public function test_user_can_delete_account_with_all_foreign_key_relationships(): void
    {
        $user = User::factory()->create([
            'google_id' => '1234567890',
        ]);
        $userId = $user->id;

        \Illuminate\Support\Facades\DB::table('participants')->insert([
            'user_id' => $userId,
            'participant_code' => 'EPY-TEST',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('telemetry_events')->insert([
            'user_id' => $userId,
            'event_name' => 'test_event',
            'event_category' => 'auth',
            'occurred_at' => now(),
            'recorded_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('user_progress')->insert([
            'user_id' => $userId,
            'total_xp' => 100,
            'current_level' => 1,
            'current_phase' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Cursos, Proyectos, Evaluaciones, Notas, Flashcards
        $courseId = \Illuminate\Support\Facades\DB::table('courses')->insertGetId([
            'user_id' => $userId,
            'name' => 'Matemáticas Discretas',
            'color' => 'blue',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $projectId = \Illuminate\Support\Facades\DB::table('course_projects')->insertGetId([
            'user_id' => $userId,
            'course_id' => $courseId,
            'title' => 'Proyecto Final',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('project_phases')->insert([
            'course_project_id' => $projectId,
            'name' => 'Fase 1',
            'color' => 'blue',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('grade_evaluations')->insert([
            'course_id' => $courseId,
            'name' => 'Examen Parcial',
            'weight' => 20.00,
            'max_score' => 20.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('flashcards')->insert([
            'user_id' => $userId,
            'course_id' => $courseId,
            'question' => '¿Qué es un grafo?',
            'answer' => 'Conjunto de nodos y aristas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $noteId = \Illuminate\Support\Facades\DB::table('course_notes')->insertGetId([
            'course_id' => $courseId,
            'user_id' => $userId,
            'content' => json_encode(['entries' => []]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('note_images')->insert([
            'note_id' => $noteId,
            'user_id' => $userId,
            'filename' => 'test-uuid-1234',
            'original_name' => 'diagram.png',
            'mime_type' => 'image/png',
            'extension' => 'png',
            'size' => 1024,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lecturas
        $readingId = \Illuminate\Support\Facades\DB::table('readings')->insertGetId([
            'user_id' => $userId,
            'title' => 'Clean Code',
            'status' => 'reading',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('reading_tags')->insert([
            'reading_id' => $readingId,
            'tag' => 'Software',
        ]);

        // Habilidades
        $skillId = \Illuminate\Support\Facades\DB::table('personal_skills')->insertGetId([
            'user_id' => $userId,
            'name' => 'Guitarra',
            'category' => 'creative',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('personal_skill_logs')->insert([
            'skill_id' => $skillId,
            'user_id' => $userId,
            'duration_minutes' => 30,
            'logged_at' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Rutinas y planificador diario
        $routineId = \Illuminate\Support\Facades\DB::table('daily_routines')->insertGetId([
            'user_id' => $userId,
            'title' => 'Meditar 10 min',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('daily_plan_items')->insert([
            'user_id' => $userId,
            'plan_date' => now()->toDateString(),
            'routine_id' => $routineId,
            'title' => 'Meditar 10 min',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Finanzas
        \Illuminate\Support\Facades\DB::table('finance_transactions')->insert([
            'user_id' => $userId,
            'type' => 'expense',
            'amount' => 15.50,
            'category' => 'comida',
            'date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Fitness
        \Illuminate\Support\Facades\DB::table('fitness_workout_logs')->insert([
            'user_id' => $userId,
            'routine_name' => 'Estiramiento',
            'performed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('daily_hydration_logs')->insert([
            'user_id' => $userId,
            'date' => now()->toDateString(),
            'glasses_count' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tienda
        $rewardId = \Illuminate\Support\Facades\DB::table('custom_rewards')->insertGetId([
            'user_id' => $userId,
            'title' => 'Ver una película',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('reward_redemptions')->insert([
            'user_id' => $userId,
            'reward_id' => $rewardId,
            'title' => 'Ver una película',
            'redeemed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Eventos y automatizaciones
        \Illuminate\Support\Facades\DB::table('personal_events')->insert([
            'user_id' => $userId,
            'title' => 'Cumpleaños de mamá',
            'event_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('automations')->insert([
            'user_id' => $userId,
            'name' => 'Auto-XP',
            'trigger_event' => 'task_done',
            'action_type' => 'notify',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Periodo académico
        \Illuminate\Support\Facades\DB::table('academic_periods')->insert([
            'user_id' => $userId,
            'year' => '2026',
            'period' => 'Semestre 1',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Grafo de conocimiento
        \Illuminate\Support\Facades\DB::table('user_knowledge_graphs')->insert([
            'user_id' => $userId,
            'nodes' => json_encode([]),
            'edges' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete('/profile');

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
        $this->assertDatabaseMissing('participants', ['user_id' => $userId]);
        $this->assertDatabaseMissing('telemetry_events', ['user_id' => $userId]);
        $this->assertDatabaseMissing('user_progress', ['user_id' => $userId]);
        $this->assertDatabaseMissing('courses', ['user_id' => $userId]);
        $this->assertDatabaseMissing('course_notes', ['user_id' => $userId]);
        $this->assertDatabaseMissing('note_images', ['user_id' => $userId]);
        $this->assertDatabaseMissing('course_projects', ['user_id' => $userId]);
        $this->assertDatabaseMissing('grade_evaluations', ['course_id' => $courseId]);
        $this->assertDatabaseMissing('flashcards', ['user_id' => $userId]);
        $this->assertDatabaseMissing('readings', ['user_id' => $userId]);
        $this->assertDatabaseMissing('reading_tags', ['reading_id' => $readingId]);
        $this->assertDatabaseMissing('personal_skills', ['user_id' => $userId]);
        $this->assertDatabaseMissing('personal_skill_logs', ['user_id' => $userId]);
        $this->assertDatabaseMissing('daily_routines', ['user_id' => $userId]);
        $this->assertDatabaseMissing('daily_plan_items', ['user_id' => $userId]);
        $this->assertDatabaseMissing('finance_transactions', ['user_id' => $userId]);
        $this->assertDatabaseMissing('fitness_workout_logs', ['user_id' => $userId]);
        $this->assertDatabaseMissing('daily_hydration_logs', ['user_id' => $userId]);
        $this->assertDatabaseMissing('custom_rewards', ['user_id' => $userId]);
        $this->assertDatabaseMissing('reward_redemptions', ['user_id' => $userId]);
        $this->assertDatabaseMissing('personal_events', ['user_id' => $userId]);
        $this->assertDatabaseMissing('automations', ['user_id' => $userId]);
        $this->assertDatabaseMissing('academic_periods', ['user_id' => $userId]);
        $this->assertDatabaseMissing('user_knowledge_graphs', ['user_id' => $userId]);
    }
}
