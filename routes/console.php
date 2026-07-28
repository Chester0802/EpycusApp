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
