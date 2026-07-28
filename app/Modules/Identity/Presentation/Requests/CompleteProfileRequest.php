<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompleteProfileRequest extends FormRequest
{
    public function rules(): array
    {
        $allCareers = collect(config('careers.styles'))->flatten()->values()->all();

        return [
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
            'career.in' => 'La carrera seleccionada no es válida.',
            'avatar_style.in' => 'El estilo de avatar no es válido.',
            'avatar_gender.in' => 'El género del avatar no es válido.',
            'cycle.min' => 'El ciclo debe estar entre 1 y 10.',
            'cycle.max' => 'El ciclo debe estar entre 1 y 10.',
            'institution_type.in' => 'El tipo de institución no es válido.',
        ];
    }
}
