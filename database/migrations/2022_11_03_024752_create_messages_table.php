<?php

use App\Models\Contacto;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('wa_id')->nullable();
            $table->string('wam_id')->unique();
            $table->string('phone_id');
            $table->string('type', 15);
            $table->boolean('outgoing');
            $table->longText('body');
            $table->string('status', 15);
            $table->longText('caption')->nullable();
            $table->binary('data');
            $table->string('distintivo');
            $table->string('code');
            $table->timestamps();

            $table->foreign('wa_id')->references('telefono')->on('contactos')->onDelete('set null');
        });
    }



    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
