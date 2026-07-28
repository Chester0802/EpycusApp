<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\Identity\Infrastructure\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
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
        ];
    }
}
