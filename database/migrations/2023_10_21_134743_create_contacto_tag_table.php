<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactoTagTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacto_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')
                ->constrained('contactos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('user_id')->after('tag_id')->constrained()->cascadeOnDelete();

            // Opcional: evitar duplicados por usuario
            $table->unique(['contacto_id', 'tag_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacto_tag');
    }
}
