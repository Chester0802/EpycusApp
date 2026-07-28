<?php

declare(strict_types=1);

return [
    'xp' => [
        'habit_completed' => env('XP_HABIT', 10),
        'pomodoro_completed' => env('XP_POMODORO', 15),
        'mission_easy' => env('XP_MISSION_EASY', 20),
        'mission_medium' => env('XP_MISSION_MEDIUM', 30),
        'mission_hard' => env('XP_MISSION_HARD', 40),
        'subtask_completed' => env('XP_SUBTASK', 5),
        'journal_entry' => env('XP_JOURNAL', 10),
        'villain_defeated' => env('XP_VILLAIN', 100),
    ],
    'daily_caps' => [
        'habits' => env('CAP_HABITS', 5),
        'pomodoros' => env('CAP_POMODOROS', 8),
        'missions' => env('CAP_MISSIONS', 3),
        'journal' => 1,
    ],
    'level_curve' => [
        'base' => 100,
        'increment' => 45,
        'max_level' => 50,
    ],
    'phases' => [
        'total' => 10,
        'levels_per_phase' => 5,
    ],
    'streak' => [
        'grace_days_per_month' => env('STREAK_GRACE', 3),
        'bonus_per_week' => 0.10,
        'bonus_max' => 0.50,
        'min_daily_actions' => 1,
    ],
    'villains' => [
        'base_hp' => 100,
        'damage_per_action' => 10,
        'difficulty_by_week' => [
            1 => 0.8,
            2 => 0.8,
            3 => 1.0,
            4 => 1.0,
            5 => 1.0,
            6 => 1.0,
            7 => 1.2,
            8 => 1.2,
            9 => 1.2,
            10 => 1.2,
        ],
    ],
    'wallet' => [
        'xp_per_coin' => 10,
    ],
];
