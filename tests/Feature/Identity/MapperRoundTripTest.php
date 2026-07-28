<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Application\Mappers\ParticipantMapper;
use App\Modules\Identity\Application\Mappers\UserMapper;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapperRoundTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_survives_a_round_trip_through_the_mapper(): void
    {
        $model = UserModel::factory()->participant()->create();

        $domain = (new UserMapper)->toDomain($model->fresh());

        $this->assertSame($model->id, $domain->id()->value());
        $this->assertSame($model->name, $domain->name());
        $this->assertSame($model->email, $domain->email());
        $this->assertSame($model->alias, $domain->alias());
        $this->assertSame($model->career, $domain->career()?->value());
        $this->assertSame($model->avatar_style, $domain->avatarStyle()?->value());
        $this->assertSame($model->avatar_gender, $domain->avatarGender()?->value());
        $this->assertSame($model->cycle, $domain->cycle()?->value());
        $this->assertSame($model->institution_type, $domain->institutionType()?->value());
    }

    public function test_participant_survives_a_round_trip_through_the_mapper(): void
    {
        $model = ParticipantModel::factory()->create([
            'student_code' => 'EST-12345',
            'whatsapp' => '+51999999999',
            'consent_granted_at' => now(),
            'enrolled_at' => now(),
        ]);

        $domain = (new ParticipantMapper)->toDomain($model->fresh());

        $this->assertSame($model->user_id, $domain->userId()->value());
        $this->assertSame($model->participant_code, $domain->participantCode()->value());
        $this->assertSame($model->student_code, $domain->studentCode());
        $this->assertSame($model->whatsapp, $domain->whatsapp());
        $this->assertTrue($domain->hasConsented());
        $this->assertNotNull($domain->enrolledAt());
        $this->assertTrue($domain->isActive());
    }
}
