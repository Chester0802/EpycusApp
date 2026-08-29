<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePreferencesRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'surface_mode' => ['sometimes', 'string', 'in:neumorphism,glass'],
            'notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
