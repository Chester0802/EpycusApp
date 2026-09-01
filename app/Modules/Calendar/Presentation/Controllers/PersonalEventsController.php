<?php

declare(strict_types=1);

namespace App\Modules\Calendar\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Calendar\Infrastructure\Models\PersonalEventModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class PersonalEventsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:birthday,meeting,appointment,social,reminder,work,other'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_recurring' => ['boolean'],
            'recurrence_rule' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);

        $event = PersonalEventModel::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'is_recurring' => (bool) ($validated['is_recurring'] ?? false),
            'recurrence_rule' => $validated['recurrence_rule'] ?? null,
            'color' => $validated['color'] ?? 'primary',
        ]);

        return response()->json([
            'message' => 'Evento personal creado con éxito.',
            'event' => $event,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $event = PersonalEventModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:birthday,meeting,appointment,social,reminder,work,other'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_recurring' => ['boolean'],
            'recurrence_rule' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);

        $event->update($validated);

        return response()->json([
            'message' => 'Evento actualizado.',
            'event' => $event,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $event = PersonalEventModel::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $event->delete();

        return response()->json([
            'message' => 'Evento eliminado.',
        ]);
    }
}
