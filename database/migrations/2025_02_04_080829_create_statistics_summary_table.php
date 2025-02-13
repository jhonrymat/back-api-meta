<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('statistics_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->constrained('newsletters')->onDelete('cascade');
            $table->date('date');
            $table->integer('sent_count')->default(0);
            $table->integer('open_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('unsubscribe_count')->default(0);
            $table->integer('delivery_count')->default(0);
            $table->integer('unknown_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->json('read_browser')->nullable();
            $table->json('read_os')->nullable();

            // Agregar columnas para navegadores
            $table->integer('firefox_count')->default(0);
            $table->integer('chrome_count')->default(0);
            $table->integer('safari_count')->default(0);
            $table->integer('edge_count')->default(0);
            $table->integer('opera_count')->default(0);
            $table->integer('unknown_browser_count')->default(0);

            // Agregar columnas para sistemas operativos
            $table->integer('windows_count')->default(0);
            $table->integer('macos_count')->default(0);
            $table->integer('linux_count')->default(0);
            $table->integer('android_count')->default(0);
            $table->integer('ios_count')->default(0);
            $table->integer('unknown_os_count')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statistics_summary');
    }
};
