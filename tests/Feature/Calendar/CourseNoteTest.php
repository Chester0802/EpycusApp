<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Modules\Calendar\Infrastructure\Models\CourseModel;
use App\Modules\Calendar\Infrastructure\Models\CourseNoteModel;
use App\Modules\Calendar\Infrastructure\Models\NoteImageModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CourseNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_empty_note_for_new_course(): void
    {
        $user = UserModel::factory()->create();
        $course = CourseModel::query()->create([
            'user_id' => $user->id,
            'name'    => 'Algoritmos',
            'color'   => 'primary',
        ]);

        $response = $this->actingAs($user)->getJson("/calendar/courses/{$course->id}/note");

        $response->assertOk()
            ->assertJson(['note' => null]);
    }

    public function test_user_can_upsert_note_with_entries(): void
    {
        $user = UserModel::factory()->create();
        $course = CourseModel::query()->create([
            'user_id' => $user->id,
            'name'    => 'Algoritmos',
            'color'   => 'primary',
        ]);

        $payload = [
            'content' => [
                'version' => '1.0',
                'entries' => [
                    [
                        'id'          => 'uuid-1',
                        'recorded_at' => '2026-08-25T10:00:00Z',
                        'blocks'      => [
                            ['type' => 'html', 'html' => '<p>Primera clase de prueba</p>'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson("/calendar/courses/{$course->id}/note", $payload);

        $response->assertOk()
            ->assertJsonPath('note.content.entries.0.id', 'uuid-1');

        $this->assertDatabaseHas('course_notes', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_user_cannot_upsert_note_for_other_user_course(): void
    {
        $userA = UserModel::factory()->create();
        $userB = UserModel::factory()->create();

        $courseB = CourseModel::query()->create([
            'user_id' => $userB->id,
            'name'    => 'Curso de B',
            'color'   => 'accent',
        ]);

        $payload = [
            'content' => [
                'version' => '1.0',
                'entries' => [
                    [
                        'id'          => 'uuid-1',
                        'recorded_at' => '2026-08-25T10:00:00Z',
                        'blocks'      => [
                            ['type' => 'html', 'html' => '<p>Hackeando</p>'],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($userA)->postJson("/calendar/courses/{$courseB->id}/note", $payload);

        $response->assertForbidden();
    }

    public function test_user_can_upload_image_to_note(): void
    {
        Storage::fake('private');

        $user = UserModel::factory()->create();
        $course = CourseModel::query()->create([
            'user_id' => $user->id,
            'name'    => 'Física I',
            'color'   => 'primary',
        ]);

        $note = CourseNoteModel::query()->create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'content'   => ['version' => '1.0', 'entries' => []],
        ]);

        // 1x1 transparent PNG
        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('pizarra.png', $pngContent);

        $response = $this->actingAs($user)->postJson('/note-images', [
            'note_id' => $note->id,
            'image'   => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('image.original_name', 'pizarra.png');

        $this->assertDatabaseHas('note_images', [
            'note_id'       => $note->id,
            'user_id'       => $user->id,
            'original_name' => 'pizarra.png',
        ]);
    }

    public function test_user_can_upsert_note_with_empty_blocks_or_empty_entries(): void
    {
        $user = UserModel::factory()->create();
        $course = CourseModel::query()->create([
            'user_id' => $user->id,
            'name'    => 'Algoritmos',
            'color'   => 'primary',
        ]);

        $payload = [
            'content' => [
                'version' => '1.0',
                'entries' => [
                    [
                        'id'          => 'uuid-empty',
                        'recorded_at' => '2026-08-25T10:00:00Z',
                        'blocks'      => [],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson("/calendar/courses/{$course->id}/note", $payload);

        $response->assertOk();
    }

    public function test_user_can_capture_image_from_camera(): void
    {
        Storage::fake('private');

        $user = UserModel::factory()->create();
        $course = CourseModel::query()->create([
            'user_id' => $user->id,
            'name'    => 'Física I',
            'color'   => 'primary',
        ]);

        $note = CourseNoteModel::query()->create([
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'content'   => ['version' => '1.0', 'entries' => []],
        ]);

        $dataUrl = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->actingAs($user)->postJson('/note-images/capture', [
            'note_id'    => $note->id,
            'image_data' => $dataUrl,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('image.mime_type', 'image/png');

        $this->assertDatabaseHas('note_images', [
            'note_id' => $note->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_view_own_image_and_cannot_view_other_user_image(): void
    {
        Storage::fake('private');

        $userA = UserModel::factory()->create();
        $userB = UserModel::factory()->create();

        $courseA = CourseModel::query()->create([
            'user_id' => $userA->id,
            'name'    => 'Curso A',
            'color'   => 'primary',
        ]);

        $noteA = CourseNoteModel::query()->create([
            'user_id'   => $userA->id,
            'course_id' => $courseA->id,
            'content'   => ['version' => '1.0', 'entries' => []],
        ]);

        $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('foto.png', $pngContent);

        $uploadRes = $this->actingAs($userA)->postJson('/note-images', [
            'note_id' => $noteA->id,
            'image'   => $file,
        ]);

        $imageId = $uploadRes->json('image.id');

        // User A can view own image
        $viewResA = $this->actingAs($userA)->get("/note-images/{$imageId}");
        $viewResA->assertOk();

        // User B cannot view user A's image
        $viewResB = $this->actingAs($userB)->get("/note-images/{$imageId}");
        $viewResB->assertForbidden();
    }
}
