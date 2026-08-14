<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecordEpaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'item_2' => ['required', 'integer', 'between:1,5'],
            'item_5' => ['required', 'integer', 'between:1,5'],
            'item_7' => ['required', 'integer', 'between:1,5'],
            'item_10' => ['required', 'integer', 'between:1,5'],
            'item_11' => ['required', 'integer', 'between:1,5'],
            'item_12' => ['required', 'integer', 'between:1,5'],
            'item_13' => ['required', 'integer', 'between:1,5'],
            'item_14' => ['required', 'integer', 'between:1,5'],
        ];
    }
}
