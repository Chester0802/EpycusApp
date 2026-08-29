<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Evalúa gracia/ruptura de rachas para el día que acaba de terminar
// (docs/03-GAMIFICACION.md §5). Corre poco después de medianoche hora de
// Lima para que "ayer" ya esté completo cuando se evalúa.
app(Schedule::class)->command('gamification:evaluate-streaks')
    ->dailyAt('00:10')
    ->timezone('America/Lima');

// Marca vencidas las misiones del día anterior (00:05 Lima)
app(Schedule::class)->command('missions:mark-overdue')
    ->dailyAt('00:05')
    ->timezone('America/Lima');

// Asigna villano semanal a todos los usuarios activos (lunes 00:00 Lima)
// docs/03-GAMIFICACION.md §6 — un villano por semana, asignado el lunes
app(Schedule::class)->command('villains:assign-weekly')
    ->weeklyOn(1, '00:00')
    ->timezone('America/Lima');

// Expira villanos de la semana anterior (domingo 23:59 Lima)
app(Schedule::class)->command('villains:expire')
    ->weeklyOn(0, '23:59')
    ->timezone('America/Lima');

// Purga mensajes de chat con más de 7 días — son efímeros y no son dato del estudio
app(Schedule::class)->command('chat:purge-old')
    ->dailyAt('03:30')
    ->timezone('America/Lima');
