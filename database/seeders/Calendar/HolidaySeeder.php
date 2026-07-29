<?php

declare(strict_types=1);

namespace Database\Seeders\Calendar;

use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

final class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Año Nuevo', 'type' => 'holiday'],
            ['date' => '2026-04-02', 'name' => 'Jueves Santo', 'type' => 'holiday'],
            ['date' => '2026-04-03', 'name' => 'Viernes Santo', 'type' => 'holiday'],
            ['date' => '2026-05-01', 'name' => 'Día del Trabajo', 'type' => 'holiday'],
            ['date' => '2026-06-07', 'name' => 'Batalla de Arica y Día de la Bandera', 'type' => 'holiday'],
            ['date' => '2026-06-29', 'name' => 'San Pedro y San Pablo', 'type' => 'holiday'],
            ['date' => '2026-07-23', 'name' => 'Día de la Fuerza Aérea', 'type' => 'holiday'],
            ['date' => '2026-07-28', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2026-07-29', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2026-08-06', 'name' => 'Batalla de Junín', 'type' => 'holiday'],
            ['date' => '2026-08-30', 'name' => 'Santa Rosa de Lima', 'type' => 'holiday'],
            ['date' => '2026-10-08', 'name' => 'Combate de Angamos', 'type' => 'holiday'],
            ['date' => '2026-11-01', 'name' => 'Todos los Santos', 'type' => 'holiday'],
            ['date' => '2026-12-08', 'name' => 'Inmaculada Concepción', 'type' => 'holiday'],
            ['date' => '2026-12-09', 'name' => 'Batalla de Ayacucho', 'type' => 'holiday'],
            ['date' => '2026-12-25', 'name' => 'Navidad', 'type' => 'holiday'],
        ];

        foreach ($holidays as $h) {
            HolidayModel::updateOrCreate(
                ['date' => $h['date']],
                $h
            );
        }
    }
}
