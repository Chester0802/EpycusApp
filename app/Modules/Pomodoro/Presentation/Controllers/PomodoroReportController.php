<?php

namespace App\Modules\Pomodoro\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pomodoro\Infrastructure\Models\PomodoroSessionModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PomodoroReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        // Buscar sesiones completadas en el mes solicitado
        $sessions = PomodoroSessionModel::where('user_id', $userId)
            ->where('status', 'COMPLETED')
            ->whereMonth('started_at', $month)
            ->whereYear('started_at', $year)
            ->get();

        $totalFocusMinutes = $sessions->sum('focus_minutes');

        // Agrupar por contexto
        $grouped = $sessions->groupBy('context_type');

        $report = [
            'total_minutes' => $totalFocusMinutes,
            'breakdown' => [
                'course' => 0, // 'mission' con 'academic' type, o 'course_project'
                'work' => 0, // 'mission' con 'work' type
                'reading' => 0,
                'skill' => 0,
                'personal' => 0, // 'mission' con 'personal' type
                'free' => 0, // 'free' o null
            ]
        ];

        // Mapeo detallado de minutos a cada categoría
        foreach ($sessions as $session) {
            $minutes = $session->focus_minutes ?? 0;
            
            switch ($session->context_type) {
                case 'mission':
                    // Para ser más precisos habría que cargar la relación de la misión
                    // pero para fines del reporte agregamos a 'course' por ahora o habría
                    // que hacer un eager loading en $sessions de la relación mission.
                    // Asumiremos que el frontend nos pasa correctamente si la mission es 'work' o 'course'
                    // Para simplicidad del backend en esta fase, guardaremos la info extra en DB
                    // o lo categorizamos según la lógica del dominio
                    if ($session->mission_id) {
                        // Idealmente: $session->mission->mission_type == 'work' ? $report['breakdown']['work'] += $minutes : ...
                        $report['breakdown']['course'] += $minutes; 
                    }
                    break;
                case 'course_project':
                    $report['breakdown']['course'] += $minutes;
                    break;
                case 'reading':
                    $report['breakdown']['reading'] += $minutes;
                    break;
                case 'skill':
                    $report['breakdown']['skill'] += $minutes;
                    break;
                case 'habit':
                    $report['breakdown']['personal'] += $minutes;
                    break;
                default:
                    $report['breakdown']['free'] += $minutes;
                    break;
            }
        }

        // Para hacerlo 100% exacto con mission_type:
        // Cargar las misiones de todas las sesiones tipo 'mission'
        $missionSessions = $sessions->where('context_type', 'mission')->whereNotNull('mission_id');
        if ($missionSessions->count() > 0) {
            $missionIds = $missionSessions->pluck('mission_id')->unique();
            $missions = \Illuminate\Support\Facades\DB::table('missions')->whereIn('id', $missionIds)->get()->keyBy('id');
            
            // Reiniciar 'course' y recalcular solo para misiones
            $report['breakdown']['course'] -= $missionSessions->sum('focus_minutes');

            foreach ($missionSessions as $session) {
                $missionType = $missions->get($session->mission_id)->mission_type ?? 'academic';
                $mins = $session->focus_minutes ?? 0;

                if ($missionType === 'work') {
                    $report['breakdown']['work'] += $mins;
                } elseif ($missionType === 'personal') {
                    $report['breakdown']['personal'] += $mins;
                } else {
                    $report['breakdown']['course'] += $mins;
                }
            }
        }

        return response()->json($report);
    }
}
