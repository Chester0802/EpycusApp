<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Modules\Gamification\Domain\Services\CharacterStatsCalculator;
use App\Modules\Gamification\Domain\Services\LevelCalculator;
use App\Modules\Identity\Infrastructure\Models\ParticipantModel;
use App\Shared\Domain\Contracts\UserProgressReaderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Modules\Achievements\Application\UseCases\GetUserAchievementsUseCase;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UserProgressReaderInterface $progressReader,
        private CharacterStatsCalculator $statsCalculator,
        private GetUserAchievementsUseCase $getUserAchievements,
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

        // Estadísticas RPG y Camino del Héroe (Fases 1 a 10)
        $characterStats = $this->statsCalculator->calculate($userId, $level, $streak);
        $herosJourneyPhases = $this->statsCalculator->getHerosJourneyPhases();

        // Cargar logros y medallas del usuario
        $achievementsData = $this->getUserAchievements->execute($userId);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => false,
            'status' => session('status'),
            'avatarStyle' => $user->avatar_style ?? 'base',
            'avatarGender' => $user->avatar_gender ?? 'm',
            'avatarOptions' => $user->avatar_options,
            'progress' => $progressData,
            'characterStats' => $characterStats,
            'herosJourneyPhases' => array_values($herosJourneyPhases),
            'achievementsData' => $achievementsData,
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

        if (empty($validated['email'])) {
            unset($validated['email']);
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

        DB::transaction(function () use ($user) {
            $userId = $user->id;
            $userEmail = $user->email;

            // 1. Telemetría y participantes (restricciones de clave foránea restrict)
            if (Schema::hasTable('telemetry_events')) {
                DB::table('telemetry_events')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('participants')) {
                DB::table('participants')->where('user_id', $userId)->delete();
            }

            // 2. Preferencias, personalización y grafo de conocimiento
            if (Schema::hasTable('user_preferences')) {
                DB::table('user_preferences')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_unlocked_wallpapers')) {
                DB::table('user_unlocked_wallpapers')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_knowledge_graphs')) {
                DB::table('user_knowledge_graphs')->where('user_id', $userId)->delete();
            }

            // 3. Progreso, XP y gamificación
            if (Schema::hasTable('xp_transactions')) {
                DB::table('xp_transactions')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_progress')) {
                DB::table('user_progress')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_skills')) {
                DB::table('user_skills')->where('user_id', $userId)->delete();
            }

            // 4. Tienda y recompensas personalizadas
            if (Schema::hasTable('reward_redemptions')) {
                DB::table('reward_redemptions')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('custom_rewards')) {
                DB::table('custom_rewards')->where('user_id', $userId)->delete();
            }

            // 5. Planificador diario y rutinas
            if (Schema::hasTable('daily_plan_items')) {
                DB::table('daily_plan_items')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('daily_routines')) {
                DB::table('daily_routines')->where('user_id', $userId)->delete();
            }

            // 6. Finanzas personales
            if (Schema::hasTable('finance_transactions')) {
                DB::table('finance_transactions')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('finance_budgets')) {
                DB::table('finance_budgets')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('finance_savings_goals')) {
                DB::table('finance_savings_goals')->where('user_id', $userId)->delete();
            }

            // 7. Fitness y bienestar físico
            if (Schema::hasTable('fitness_workout_logs')) {
                DB::table('fitness_workout_logs')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('daily_hydration_logs')) {
                DB::table('daily_hydration_logs')->where('user_id', $userId)->delete();
            }

            // 8. Habilidades personales (personal_skills y personal_skill_logs)
            if (Schema::hasTable('personal_skills')) {
                $personalSkillIds = DB::table('personal_skills')->where('user_id', $userId)->pluck('id');
                if ($personalSkillIds->isNotEmpty() && Schema::hasTable('personal_skill_logs')) {
                    DB::table('personal_skill_logs')->whereIn('skill_id', $personalSkillIds)->delete();
                }
                if (Schema::hasTable('personal_skill_logs')) {
                    DB::table('personal_skill_logs')->where('user_id', $userId)->delete();
                }
                DB::table('personal_skills')->where('user_id', $userId)->delete();
            }

            // 9. Lecturas y etiquetas (readings y reading_tags)
            if (Schema::hasTable('readings')) {
                if (Schema::hasTable('reading_tags')) {
                    $readingIds = DB::table('readings')->where('user_id', $userId)->pluck('id');
                    if ($readingIds->isNotEmpty()) {
                        DB::table('reading_tags')->whereIn('reading_id', $readingIds)->delete();
                    }
                }
                DB::table('readings')->where('user_id', $userId)->delete();
            }

            // 10. Flashcards
            if (Schema::hasTable('flashcards')) {
                DB::table('flashcards')->where('user_id', $userId)->delete();
            }

            // 11. Eventos personales y automatizaciones
            if (Schema::hasTable('personal_events')) {
                DB::table('personal_events')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('automations')) {
                DB::table('automations')->where('user_id', $userId)->delete();
            }

            // 12. Hábitos y completitud
            if (Schema::hasTable('habits')) {
                $habitIds = DB::table('habits')->where('user_id', $userId)->pluck('id');
                if ($habitIds->isNotEmpty() && Schema::hasTable('habit_completions')) {
                    DB::table('habit_completions')->whereIn('habit_id', $habitIds)->delete();
                }
                if (Schema::hasTable('habit_completions')) {
                    DB::table('habit_completions')->where('user_id', $userId)->delete();
                }
                DB::table('habits')->where('user_id', $userId)->delete();
            }

            // 13. Misiones y subtareas
            if (Schema::hasTable('missions')) {
                $missionIds = DB::table('missions')->where('user_id', $userId)->pluck('id');
                if ($missionIds->isNotEmpty()) {
                    if (Schema::hasTable('pomodoro_session_subtask') && Schema::hasTable('subtasks')) {
                        DB::table('pomodoro_session_subtask')->whereIn('subtask_id', function ($query) use ($missionIds) {
                            $query->select('id')->from('subtasks')->whereIn('mission_id', $missionIds);
                        })->delete();
                    }
                    if (Schema::hasTable('subtasks')) {
                        DB::table('subtasks')->whereIn('mission_id', $missionIds)->delete();
                    }
                }
                DB::table('missions')->where('user_id', $userId)->delete();
            }

            // 14. Pomodoro
            if (Schema::hasTable('pomodoro_sessions')) {
                $pomodoroIds = DB::table('pomodoro_sessions')->where('user_id', $userId)->pluck('id');
                if ($pomodoroIds->isNotEmpty() && Schema::hasTable('pomodoro_session_subtask')) {
                    DB::table('pomodoro_session_subtask')->whereIn('pomodoro_session_id', $pomodoroIds)->delete();
                }
                DB::table('pomodoro_sessions')->where('user_id', $userId)->delete();
            }

            // 15. Villanos
            if (Schema::hasTable('villain_instances')) {
                DB::table('villain_instances')->where('user_id', $userId)->delete();
            }

            // 16. Grupos de estudio y chat
            if (Schema::hasTable('chat_messages')) {
                DB::table('chat_messages')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('session_participants')) {
                DB::table('session_participants')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('study_sessions')) {
                $hostedSessions = DB::table('study_sessions')->where('host_id', $userId)->pluck('id');
                if ($hostedSessions->isNotEmpty()) {
                    if (Schema::hasTable('chat_messages')) {
                        DB::table('chat_messages')->whereIn('session_id', $hostedSessions)->delete();
                    }
                    if (Schema::hasTable('session_participants')) {
                        DB::table('session_participants')->whereIn('session_id', $hostedSessions)->delete();
                    }
                    DB::table('study_sessions')->whereIn('id', $hostedSessions)->delete();
                }
            }

            // 17. Calendario, Cursos, Evaluaciones, Proyectos, Horarios, Notas e Imágenes
            if (Schema::hasTable('class_schedules')) {
                DB::table('class_schedules')->where('user_id', $userId)->delete();
            }

            if (Schema::hasTable('note_images')) {
                DB::table('note_images')->where('user_id', $userId)->delete();
            }

            if (Schema::hasTable('course_notes')) {
                DB::table('course_notes')->where('user_id', $userId)->delete();
            }

            if (Schema::hasTable('course_projects')) {
                $courseProjectIds = DB::table('course_projects')->where('user_id', $userId)->pluck('id');
                if ($courseProjectIds->isNotEmpty() && Schema::hasTable('project_phases')) {
                    DB::table('project_phases')->whereIn('course_project_id', $courseProjectIds)->delete();
                }
                DB::table('course_projects')->where('user_id', $userId)->delete();
            }

            if (Schema::hasTable('courses')) {
                $courseIds = DB::table('courses')->where('user_id', $userId)->pluck('id');
                if ($courseIds->isNotEmpty()) {
                    if (Schema::hasTable('course_projects')) {
                        $extraProjectIds = DB::table('course_projects')->whereIn('course_id', $courseIds)->pluck('id');
                        if ($extraProjectIds->isNotEmpty()) {
                            if (Schema::hasTable('project_phases')) {
                                DB::table('project_phases')->whereIn('course_project_id', $extraProjectIds)->delete();
                            }
                            DB::table('course_projects')->whereIn('id', $extraProjectIds)->delete();
                        }
                    }
                    if (Schema::hasTable('grade_evaluations')) {
                        DB::table('grade_evaluations')->whereIn('course_id', $courseIds)->delete();
                    }
                    if (Schema::hasTable('flashcards')) {
                        DB::table('flashcards')->whereIn('course_id', $courseIds)->delete();
                    }
                    if (Schema::hasTable('course_notes')) {
                        $noteIds = DB::table('course_notes')->whereIn('course_id', $courseIds)->pluck('id');
                        if ($noteIds->isNotEmpty() && Schema::hasTable('note_images')) {
                            DB::table('note_images')->whereIn('note_id', $noteIds)->delete();
                        }
                        DB::table('course_notes')->whereIn('course_id', $courseIds)->delete();
                    }
                    if (Schema::hasTable('course_sessions')) {
                        DB::table('course_sessions')->whereIn('course_id', $courseIds)->delete();
                    }
                    DB::table('courses')->where('user_id', $userId)->delete();
                }
            }

            if (Schema::hasTable('academic_periods')) {
                DB::table('academic_periods')->where('user_id', $userId)->delete();
            }

            // 18. Diario y bienestar
            if (Schema::hasTable('journal_entries')) {
                DB::table('journal_entries')->where('user_id', $userId)->delete();
            }

            // 19. Asistente IA
            if (Schema::hasTable('ai_conversations')) {
                $conversationIds = DB::table('ai_conversations')->where('user_id', $userId)->pluck('id');
                if ($conversationIds->isNotEmpty() && Schema::hasTable('ai_messages')) {
                    DB::table('ai_messages')->whereIn('conversation_id', $conversationIds)->delete();
                }
                DB::table('ai_conversations')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('ai_quotas')) {
                DB::table('ai_quotas')->where('user_id', $userId)->delete();
            }

            // 20. EPA
            if (Schema::hasTable('epa_responses')) {
                DB::table('epa_responses')->where('user_id', $userId)->delete();
            }

            // 21. Logros y motivación
            if (Schema::hasTable('user_achievements')) {
                DB::table('user_achievements')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_tip_views')) {
                DB::table('user_tip_views')->where('user_id', $userId)->delete();
            }
            if (Schema::hasTable('user_quote_views')) {
                DB::table('user_quote_views')->where('user_id', $userId)->delete();
            }

            // 22. Sesiones web y tokens
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $userId)->delete();
            }
            if (!empty($userEmail) && Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $userEmail)->delete();
            }

            // 23. Finalmente eliminar el registro en users
            DB::table('users')->where('id', $userId)->delete();
        });

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
