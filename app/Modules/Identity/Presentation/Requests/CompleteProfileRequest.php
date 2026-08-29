<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Requests;

use App\Modules\Identity\Domain\ValueObjects\Career;
use Illuminate\Foundation\Http\FormRequest;

final class CompleteProfileRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $allCareers = Career::all();

        return [
            'alias' => ['required', 'string', 'max:40', 'unique:users,alias,'.($this->user()?->id ?? 'NULL')],
            'career' => ['required', 'string', 'in:'.implode(',', $allCareers)],
            'avatar_style' => ['required', 'string', 'in:health,business,technical,systems,law'],
            'avatar_gender' => ['required', 'string', 'in:m,f'],
            'cycle' => ['required', 'integer', 'min:1', 'max:10'],
            'institution_type' => ['required', 'string', 'in:universidad,instituto'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique' => 'Este alias ya está en uso por otro estudiante.',
            'career.in' => 'La carrera seleccionada no es válida.',
            'avatar_style.in' => 'El estilo de avatar no es válido.',
            'avatar_gender.in' => 'El género del avatar no es válido.',
            'cycle.min' => 'El ciclo debe estar entre 1 y 10.',
            'cycle.max' => 'El ciclo debe estar entre 1 y 10.',
            'institution_type.in' => 'El tipo de institución no es válido.',
        ];
    }
}
