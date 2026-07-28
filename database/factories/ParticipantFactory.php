<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Identity\Domain\ValueObjects\ParticipantCode;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantModel>
 */
final class ParticipantFactory extends Factory
{
    protected $model = ParticipantModel::class;

    public function definition(): array
    {
        return [
            'user_id' => UserModel::factory(),
            'participant_code' => ParticipantCode::generate()->value(),
            'consent_granted_at' => null,
            'enrolled_at' => null,
            'withdrawn_at' => null,
        ];
    }
}
