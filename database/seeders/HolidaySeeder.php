<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Calendar\Infrastructure\Models\HolidayModel;
use Illuminate\Database\Seeder;

final class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // ── 2025 ────────────────────────────────────────────────────────
            ['date' => '2025-01-01', 'name' => 'Año Nuevo', 'type' => 'holiday'],
            ['date' => '2025-04-17', 'name' => 'Jueves Santo', 'type' => 'holiday'],
            ['date' => '2025-04-18', 'name' => 'Viernes Santo', 'type' => 'holiday'],
            ['date' => '2025-05-01', 'name' => 'Día del Trabajo', 'type' => 'holiday'],
            ['date' => '2025-06-07', 'name' => 'Batalla de Arica y Día de la Bandera', 'type' => 'holiday'],
            ['date' => '2025-06-29', 'name' => 'San Pedro y San Pablo', 'type' => 'holiday'],
            ['date' => '2025-07-23', 'name' => 'Día de la Fuerza Aérea del Perú', 'type' => 'holiday'],
            ['date' => '2025-07-28', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2025-07-29', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2025-08-06', 'name' => 'Batalla de Junín', 'type' => 'holiday'],
            ['date' => '2025-08-30', 'name' => 'Santa Rosa de Lima', 'type' => 'holiday'],
            ['date' => '2025-10-08', 'name' => 'Combate de Angamos', 'type' => 'holiday'],
            ['date' => '2025-11-01', 'name' => 'Día de Todos los Santos', 'type' => 'holiday'],
            ['date' => '2025-12-08', 'name' => 'Inmaculada Concepción', 'type' => 'holiday'],
            ['date' => '2025-12-09', 'name' => 'Batalla de Ayacucho', 'type' => 'holiday'],
            ['date' => '2025-12-25', 'name' => 'Navidad', 'type' => 'holiday'],

            // ── 2026 ────────────────────────────────────────────────────────
            ['date' => '2026-01-01', 'name' => 'Año Nuevo', 'type' => 'holiday'],
            ['date' => '2026-04-02', 'name' => 'Jueves Santo', 'type' => 'holiday'],
            ['date' => '2026-04-03', 'name' => 'Viernes Santo', 'type' => 'holiday'],
            ['date' => '2026-05-01', 'name' => 'Día del Trabajo', 'type' => 'holiday'],
            ['date' => '2026-06-07', 'name' => 'Batalla de Arica y Día de la Bandera', 'type' => 'holiday'],
            ['date' => '2026-06-29', 'name' => 'San Pedro y San Pablo', 'type' => 'holiday'],
            ['date' => '2026-07-23', 'name' => 'Día de la Fuerza Aérea del Perú', 'type' => 'holiday'],
            ['date' => '2026-07-28', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2026-07-29', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2026-08-06', 'name' => 'Batalla de Junín', 'type' => 'holiday'],
            ['date' => '2026-08-30', 'name' => 'Santa Rosa de Lima', 'type' => 'holiday'],
            ['date' => '2026-10-08', 'name' => 'Combate de Angamos', 'type' => 'holiday'],
            ['date' => '2026-11-01', 'name' => 'Día de Todos los Santos', 'type' => 'holiday'],
            ['date' => '2026-12-08', 'name' => 'Inmaculada Concepción', 'type' => 'holiday'],
            ['date' => '2026-12-09', 'name' => 'Batalla de Ayacucho', 'type' => 'holiday'],
            ['date' => '2026-12-25', 'name' => 'Navidad', 'type' => 'holiday'],

            // ── 2027 ────────────────────────────────────────────────────────
            ['date' => '2027-01-01', 'name' => 'Año Nuevo', 'type' => 'holiday'],
            ['date' => '2027-03-25', 'name' => 'Jueves Santo', 'type' => 'holiday'],
            ['date' => '2027-03-26', 'name' => 'Viernes Santo', 'type' => 'holiday'],
            ['date' => '2027-05-01', 'name' => 'Día del Trabajo', 'type' => 'holiday'],
            ['date' => '2027-06-07', 'name' => 'Batalla de Arica y Día de la Bandera', 'type' => 'holiday'],
            ['date' => '2027-06-29', 'name' => 'San Pedro y San Pablo', 'type' => 'holiday'],
            ['date' => '2027-07-23', 'name' => 'Día de la Fuerza Aérea del Perú', 'type' => 'holiday'],
            ['date' => '2027-07-28', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2027-07-29', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2027-08-06', 'name' => 'Batalla de Junín', 'type' => 'holiday'],
            ['date' => '2027-08-30', 'name' => 'Santa Rosa de Lima', 'type' => 'holiday'],
            ['date' => '2027-10-08', 'name' => 'Combate de Angamos', 'type' => 'holiday'],
            ['date' => '2027-11-01', 'name' => 'Día de Todos los Santos', 'type' => 'holiday'],
            ['date' => '2027-12-08', 'name' => 'Inmaculada Concepción', 'type' => 'holiday'],
            ['date' => '2027-12-09', 'name' => 'Batalla de Ayacucho', 'type' => 'holiday'],
            ['date' => '2027-12-25', 'name' => 'Navidad', 'type' => 'holiday'],

            // ── 2028 ────────────────────────────────────────────────────────
            ['date' => '2028-01-01', 'name' => 'Año Nuevo', 'type' => 'holiday'],
            ['date' => '2028-04-13', 'name' => 'Jueves Santo', 'type' => 'holiday'],
            ['date' => '2028-04-14', 'name' => 'Viernes Santo', 'type' => 'holiday'],
            ['date' => '2028-05-01', 'name' => 'Día del Trabajo', 'type' => 'holiday'],
            ['date' => '2028-06-07', 'name' => 'Batalla de Arica y Día de la Bandera', 'type' => 'holiday'],
            ['date' => '2028-06-29', 'name' => 'San Pedro y San Pablo', 'type' => 'holiday'],
            ['date' => '2028-07-23', 'name' => 'Día de la Fuerza Aérea del Perú', 'type' => 'holiday'],
            ['date' => '2028-07-28', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2028-07-29', 'name' => 'Fiestas Patrias', 'type' => 'holiday'],
            ['date' => '2028-08-06', 'name' => 'Batalla de Junín', 'type' => 'holiday'],
            ['date' => '2028-08-30', 'name' => 'Santa Rosa de Lima', 'type' => 'holiday'],
            ['date' => '2028-10-08', 'name' => 'Combate de Angamos', 'type' => 'holiday'],
            ['date' => '2028-11-01', 'name' => 'Día de Todos los Santos', 'type' => 'holiday'],
            ['date' => '2028-12-08', 'name' => 'Inmaculada Concepción', 'type' => 'holiday'],
            ['date' => '2028-12-09', 'name' => 'Batalla de Ayacucho', 'type' => 'holiday'],
            ['date' => '2028-12-25', 'name' => 'Navidad', 'type' => 'holiday'],
        ];

        foreach ($holidays as $h) {
            HolidayModel::updateOrCreate(
                ['date' => $h['date']],
                $h
            );
        }
    }
}
