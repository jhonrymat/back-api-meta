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
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');   
            $table->string('name'); // Nombre del boletín
            $table->string('subject'); // Asunto del correo
            $table->string('copy_email')->nullable(); // Correo de copia (opcional)
            $table->boolean('has_attachment')->default(false); // Indica si tiene archivo adjunto
            $table->string('attachment_path')->nullable(); // Ruta del archivo adjunto (opcional)
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newsletters');
    }
};
