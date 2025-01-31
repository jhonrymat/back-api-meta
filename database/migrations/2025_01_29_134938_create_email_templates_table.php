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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Relación con el usuario
            $table->string('name'); // Nombre de la plantilla
            $table->longText('html_content'); // Contenido HTML editable con Summernote
            $table->string('logo')->nullable(); // URL del logo
            $table->string('card_background_color')->default('#ffffff'); // Fondo externo de la tarjeta
            $table->string('header_color')->default('#12b5ec'); // Color del encabezado
            $table->string('footer_color')->default('#f1f1f1'); // Color del footer
            $table->longText('title')->nullable(); // Título principal editable con Summernote
            $table->longText('footer_text')->nullable(); // Texto del pie de página editable con Summernote
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
