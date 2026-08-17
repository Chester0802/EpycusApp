<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progressReader,
    ) {}

    public function edit(Request $request): Response
    {
        $user = $request->user();
        $userId = (int) $user->id;
        $participant = ParticipantModel::where('user_id', $userId)->first();

        // Cargar progreso de gamificación real del usuario
        $level = $this->progressReader->getLevelFor($userId);
        $phase = $this->progressReader->getPhaseFor($userId);
        $totalXp = $this->progressReader->getTotalXpFor($userId);
        $streak = $this->progressReader->getCurrentStreakFor($userId);
        $coins = $this->progressReader->getCoinsFor($userId);

        $levelCalc = app(LevelCalculator::class);
        $accumulated = 0;
        for ($l = 1; $l < $level; $l++) {
            $accumulated += $levelCalc->xpNeededToAdvanceFromLevel($l);
        }
        $currentLevelXp = max(0, $totalXp - $accumulated);
        $nextLevelXpNeeded = $levelCalc->xpNeededToAdvanceFromLevel($level);
        $levelProgressPercent = $nextLevelXpNeeded > 0
            ? min(100, (int) round(($currentLevelXp / $nextLevelXpNeeded) * 100))
            : 100;

        $progressData = [
            'level' => $level,
            'phase' => $phase,
            'totalXp' => $totalXp,
            'currentStreak' => $streak,
            'coins' => $coins,
            'currentLevelXp' => $currentLevelXp,
            'nextLevelXpNeeded' => $nextLevelXpNeeded,
            'levelProgressPercent' => $levelProgressPercent,
        ];

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
            'avatarStyle' => $user->avatar_style ?? 'base',
            'avatarGender' => $user->avatar_gender ?? 'm',
            'avatarOptions' => $user->avatar_options,
            'progress' => $progressData,
            'participantCode' => $participant?->participant_code,
            'careers' => config('careers.styles'),
            'cycles' => config('careers.cycles'),
            'institutionTypes' => config('careers.institution_types'),
            'profileData' => [
                'alias' => $user->alias,
                'career' => $user->career,
                'cycle' => $user->cycle,
                'avatarGender' => $user->avatar_gender,
                'institutionType' => $user->institution_type,
            ],
        ]);
    }

    /**
     * Update the user's customized avatar options.
     */
    public function updateAvatarOptions(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'skinColor' => ['nullable', 'string', 'max:10'],
            'head' => ['nullable', 'string', 'max:50'],
            'face' => ['nullable', 'string', 'max:50'],
            'accessories' => ['nullable', 'string', 'max:50'],
            'facialHair' => ['nullable', 'string', 'max:50'],
            'clothingColor' => ['nullable', 'string', 'max:10'],
            'backgroundColor' => ['nullable', 'string', 'max:10'],
        ]);

        $user = $request->user();
        $user->avatar_options = array_filter($validated, fn ($v) => $v !== null);
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Avatar personalizado guardado.');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! empty($validated['career'])) {
            $validated['avatar_style'] = \App\Modules\Identity\Domain\ValueObjects\Career::avatarStyle($validated['career']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (empty($user->google_id)) {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);
        }

        Auth::logout();

        DB::transaction(function () use ($user) {
            $userId = $user->id;

            // 1. Telemetría y participantes (restricciones de clave foránea)
            DB::table('telemetry_events')->where('user_id', $userId)->delete();
            DB::table('participants')->where('user_id', $userId)->delete();

            // 2. Preferencias y personalización
            DB::table('user_preferences')->where('user_id', $userId)->delete();
            DB::table('user_unlocked_wallpapers')->where('user_id', $userId)->delete();

            // 3. Progreso y gamificación
            DB::table('xp_transactions')->where('user_id', $userId)->delete();
            DB::table('user_progress')->where('user_id', $userId)->delete();

            // 4. Hábitos y completitud
            $habitIds = DB::table('habits')->where('user_id', $userId)->pluck('id');
            if ($habitIds->isNotEmpty()) {
                DB::table('habit_completions')->whereIn('habit_id', $habitIds)->delete();
            }
            DB::table('habit_completions')->where('user_id', $userId)->delete();
            DB::table('habits')->where('user_id', $userId)->delete();

            // 5. Misiones y subtareas
            $missionIds = DB::table('missions')->where('user_id', $userId)->pluck('id');
            if ($missionIds->isNotEmpty()) {
                DB::table('pomodoro_session_subtask')->whereIn('subtask_id', function ($query) use ($missionIds) {
                    $query->select('id')->from('subtasks')->whereIn('mission_id', $missionIds);
                })->delete();
                DB::table('subtasks')->whereIn('mission_id', $missionIds)->delete();
            }
            DB::table('missions')->where('user_id', $userId)->delete();

            // 6. Pomodoro
            $pomodoroIds = DB::table('pomodoro_sessions')->where('user_id', $userId)->pluck('id');
            if ($pomodoroIds->isNotEmpty()) {
                DB::table('pomodoro_session_subtask')->whereIn('pomodoro_session_id', $pomodoroIds)->delete();
            }
            DB::table('pomodoro_sessions')->where('user_id', $userId)->delete();

            // 7. Villanos
            DB::table('villain_instances')->where('user_id', $userId)->delete();

            // 8. Grupos de estudio y chat
            DB::table('chat_messages')->where('user_id', $userId)->delete();
            DB::table('session_participants')->where('user_id', $userId)->delete();
            $hostedSessions = DB::table('study_sessions')->where('host_id', $userId)->pluck('id');
            if ($hostedSessions->isNotEmpty()) {
                DB::table('chat_messages')->whereIn('session_id', $hostedSessions)->delete();
                DB::table('session_participants')->whereIn('session_id', $hostedSessions)->delete();
                DB::table('study_sessions')->whereIn('id', $hostedSessions)->delete();
            }

            // 9. Calendario, Cursos, Horarios y Notas
            DB::table('class_schedules')->where('user_id', $userId)->delete();
            $courseIds = DB::table('courses')->where('user_id', $userId)->pluck('id');
            if ($courseIds->isNotEmpty()) {
                $noteIds = DB::table('course_notes')->whereIn('course_id', $courseIds)->pluck('id');
                if ($noteIds->isNotEmpty()) {
                    DB::table('note_images')->whereIn('course_note_id', $noteIds)->delete();
                }
                DB::table('course_notes')->whereIn('course_id', $courseIds)->delete();
                DB::table('course_sessions')->whereIn('course_id', $courseIds)->delete();
                DB::table('courses')->where('user_id', $userId)->delete();
            }

            // 10. Diario y bienestar
            DB::table('journal_entries')->where('user_id', $userId)->delete();

            // 11. Asistente IA
            $conversationIds = DB::table('ai_conversations')->where('user_id', $userId)->pluck('id');
            if ($conversationIds->isNotEmpty()) {
                DB::table('ai_messages')->whereIn('conversation_id', $conversationIds)->delete();
            }
            DB::table('ai_conversations')->where('user_id', $userId)->delete();
            DB::table('ai_quotas')->where('user_id', $userId)->delete();

            // 12. EPA
            DB::table('epa_responses')->where('user_id', $userId)->delete();

            // 13. Logros y motivación
            DB::table('user_achievements')->where('user_id', $userId)->delete();
            DB::table('user_tip_views')->where('user_id', $userId)->delete();
            DB::table('user_quote_views')->where('user_id', $userId)->delete();

            // 14. Finalmente eliminar el registro en users
            DB::table('users')->where('id', $userId)->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
