<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra los datos existentes de class_schedules → courses + course_sessions.
 * NO elimina class_schedules (se mantiene como respaldo).
 * Cada fila de class_schedules crea un curso con una sola session.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schedules = DB::table('class_schedules')->get();

        foreach ($schedules as $schedule) {
            $courseId = DB::table('courses')->insertGetId([
                'user_id'    => $schedule->user_id,
                'name'       => $schedule->course_name,
                'color'      => $schedule->color,
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
            ]);

            DB::table('course_sessions')->insert([
                'course_id'  => $courseId,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => $schedule->start_time,
                'end_time'   => $schedule->end_time,
                'classroom'  => $schedule->classroom,
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        // No revertimos: los datos originales siguen en class_schedules
        DB::table('course_sessions')->delete();
        DB::table('courses')->delete();
    }
};
