<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(UserModel::class)->ignore($this->user()->id),
            ],
            'alias' => ['nullable', 'string', 'max:40', Rule::unique(UserModel::class)->ignore($this->user()->id)],
            'career' => ['nullable', 'string', 'max:60'],
            'cycle' => ['nullable', 'integer', 'between:1,10'],
            'institution_type' => ['nullable', 'string', 'in:universidad,instituto'],
        ];
    }
}
