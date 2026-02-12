<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->unsignedBigInteger('size'); // en bytes
            $table->string('extension', 10);
            $table->json('metadata')->nullable(); // dimensiones, duración, etc.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['mime_type', 'created_at']);
        });

        // Tabla para relaciones polimórficas (opcional pero útil)
        Schema::create('mediables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_id');
            $table->morphs('mediable');
            $table->string('tag')->nullable(); // para categorizar (ej: 'gallery', 'featured_image')
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediables');
        Schema::dropIfExists('media');
    }
};
