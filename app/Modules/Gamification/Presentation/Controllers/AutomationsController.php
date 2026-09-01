<?php

declare(strict_types=1);

namespace App\Modules\Gamification\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gamification\Domain\Services\AutomationsService;
use App\Modules\Gamification\Infrastructure\Models\AutomationModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class AutomationsController extends Controller
{
    public function __construct(
        private readonly AutomationsService $automationsService,
    ) {}

    public function index(): JsonResponse
    {
        $userId = (int) Auth::id();

        $automations = AutomationModel::forUser($userId)->get();

        if ($automations->isEmpty()) {
            foreach (AutomationsService::defaultAutomations() as $default) {
                AutomationModel::create([
                    'user_id' => $userId,
                    'name' => $default['name'],
                    'trigger_event' => $default['trigger_event'],
                    'action_type' => $default['action_type'],
                    'config' => $default['config'],
                    'is_active' => true,
                ]);
            }
            $automations = AutomationModel::forUser($userId)->get();
        }

        return response()->json([
            'automations' => $automations,
            'defaults' => AutomationsService::defaultAutomations(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'trigger_event' => ['required', 'string', 'max:60'],
            'action_type' => ['required', 'string', 'max:60'],
            'config' => ['nullable', 'array'],
        ]);

        $automation = AutomationModel::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'trigger_event' => $validated['trigger_event'],
            'action_type' => $validated['action_type'],
            'config' => $validated['config'] ?? [],
            'is_active' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Automatización creada', 'automation' => $automation]);
        }

        return back()->with('success', 'Automatización activada.');
    }

    public function toggle(int $id): JsonResponse
    {
        $userId = (int) Auth::id();
        $automation = AutomationModel::where('id', $id)->where('user_id', $userId)->firstOrFail();

        $automation->is_active = ! $automation->is_active;
        $automation->save();

        return response()->json([
            'message' => $automation->is_active ? 'Automatización activada' : 'Automatización pausada',
            'automation' => $automation,
        ]);
    }

    public function runRules(): JsonResponse
    {
        $userId = (int) Auth::id();
        $updatedMissions = $this->automationsService->runMissionDueSoonRules($userId);

        return response()->json([
            'message' => "Reglas ejecutadas: {$updatedMissions} misiones auto-priorizadas.",
            'updated_missions' => $updatedMissions,
        ]);
    }

    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $userId = (int) Auth::id();
        $automation = AutomationModel::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $automation->delete();

        return response()->json(['message' => 'Automatización eliminada.']);
    }
}
