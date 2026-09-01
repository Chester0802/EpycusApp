<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use App\Modules\Readings\Infrastructure\Models\ReadingModel;
use App\Modules\Skills\Infrastructure\Models\SkillModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReadingsAndSkillsTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = UserModel::factory()->create();
    }

    public function test_user_can_create_and_manage_readings(): void
    {
        $this->actingAs($this->user);

        // 1. Create reading
        $response = $this->post(route('readings.store'), [
            'title' => 'Hábitos Atómicos',
            'author' => 'James Clear',
            'year' => 2018,
            'type' => 'book_nonfiction',
            'total_pages' => 320,
            'status' => 'reading',
            'current_page' => 50,
            'tags' => ['psicología', 'desarrollo'],
        ]);

        $response->assertRedirect();

        $reading = ReadingModel::where('user_id', $this->user->id)->first();
        $this->assertNotNull($reading);
        $this->assertSame('Hábitos Atómicos', $reading->title);
        $this->assertSame(50, $reading->current_page);
        $this->assertCount(2, $reading->tags);

        // 2. Advance reading progress
        $progressRes = $this->postJson(route('readings.progress', ['id' => $reading->id]), [
            'current_page' => 100,
        ]);

        $progressRes->assertOk();
        $this->assertDatabaseHas('readings', [
            'id' => $reading->id,
            'current_page' => 100,
        ]);

        // 3. Finish reading
        $finishRes = $this->postJson(route('readings.progress', ['id' => $reading->id]), [
            'current_page' => 320,
        ]);

        $finishRes->assertOk();
        $this->assertTrue($finishRes->json('is_finished'));
        $this->assertDatabaseHas('readings', [
            'id' => $reading->id,
            'status' => 'finished',
        ]);
    }

    public function test_user_can_create_skills_and_log_practice_with_level_up(): void
    {
        $this->actingAs($this->user);

        // 1. Create skill
        $response = $this->post(route('skills.store'), [
            'name' => 'Python & Algoritmos',
            'category' => 'technical',
            'description' => 'Estructuras de datos y resolución de problemas.',
        ]);

        $response->assertRedirect();

        $skill = SkillModel::where('user_id', $this->user->id)->first();
        $this->assertNotNull($skill);
        $this->assertSame('Python & Algoritmos', $skill->name);
        $this->assertSame(1, $skill->current_level);

        // 2. Log practice session (90 min => 135 XP => levels up past 100 XP)
        $practiceRes = $this->postJson(route('skills.practice', ['id' => $skill->id]), [
            'duration_minutes' => 90,
            'notes' => 'Resolví 3 ejercicios de grafos.',
        ]);

        $practiceRes->assertOk();
        $this->assertTrue($practiceRes->json('leveled_up'));
        $this->assertSame(2, $practiceRes->json('skill.current_level'));

        $this->assertDatabaseHas('personal_skill_logs', [
            'skill_id' => $skill->id,
            'duration_minutes' => 90,
            'user_id' => $this->user->id,
        ]);
    }
}
