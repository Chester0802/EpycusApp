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
                'sometimes',
                'nullable',
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

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.email' => 'Ingresa un correo electrónico con formato válido.',
            'email.unique' => 'Este correo electrónico ya se encuentra registrado en otra cuenta.',
            'alias.unique' => 'Este alias ya está en uso por otro estudiante.',
            'alias.max' => 'El alias no debe superar los 40 caracteres.',
            'cycle.between' => 'El ciclo académico debe estar entre 1 y 10.',
            'institution_type.in' => 'El tipo de institución debe ser Universidad o Instituto.',
        ];
    }
}
