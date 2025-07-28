<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stats_diarios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('phone_id');
            $table->string('status');
            $table->unsignedBigInteger('total')->default(0);
            $table->string('distintivo')->nullable(); // Campo extra de messages
            $table->timestamps();

            $table->unique(['fecha', 'phone_id', 'status', 'distintivo'], 'stats_unique_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_diarios');
    }
};
