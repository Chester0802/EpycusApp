<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villains', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('code', 32)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->text('weakness_description')->nullable();
            $table->timestamps();
        });

        Schema::create('villain_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('villain_id');
            $table->unsignedSmallInteger('week_number');
            $table->unsignedSmallInteger('total_hp');
            $table->unsignedSmallInteger('remaining_hp');
            $table->enum('status', ['active', 'defeated', 'survived'])->default('active');
            $table->dateTime('assigned_at');
            $table->dateTime('expires_at');
            $table->dateTime('defeated_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'week_number']);
            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        DB::table('villains')->insert([
            [
                'code' => 'procrastination',
                'name' => 'La Postergación',
                'description' => 'El arte de dejar todo para después acecha tu productividad.',
                'weakness_description' => 'Se debilita cuando completas misiones antes de su vencimiento.',
            ],
            [
                'code' => 'distraction',
                'name' => 'La Distracción',
                'description' => 'El zumbido del celular y las redes sociales te alejan del enfoque.',
                'weakness_description' => 'Se debilita cuando completas pomodoros sin abandonar.',
            ],
            [
                'code' => 'anxiety',
                'name' => 'La Ansiedad',
                'description' => 'El agobio ante la carga de trabajo nubla tu mente.',
                'weakness_description' => 'Se debilita cuando escribes en tu diario y cumples hábitos.',
            ],
            [
                'code' => 'disorder',
                'name' => 'El Desorden',
                'description' => 'No saber por dónde empezar te paraliza.',
                'weakness_description' => 'Se debilita cuando creas misiones con subtareas.',
            ],
            [
                'code' => 'fatigue',
                'name' => 'El Cansancio',
                'description' => 'Llegas agotado de clases y el descanso se vuelve tu prioridad.',
                'weakness_description' => 'Se debilita cuando cumples hábitos de descanso y sueño.',
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('villain_instances');
        Schema::dropIfExists('villains');
    }
};
