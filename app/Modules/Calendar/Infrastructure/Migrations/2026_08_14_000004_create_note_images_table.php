<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('note_id')->constrained('course_notes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('filename', 80)->comment('UUID generado — nombre en disco, sin extensión original');
            $table->string('original_name', 255)->comment('Nombre original del archivo');
            $table->string('mime_type', 50)->comment('MIME real verificado por magic bytes');
            $table->string('extension', 10)->comment('Extensión real detectada por finfo');
            $table->unsignedBigInteger('size')->comment('Tamaño en bytes');
            $table->timestamps();

            $table->index(['note_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_images');
    }
};
