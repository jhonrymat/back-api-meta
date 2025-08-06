<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportacionFallida extends Mailable
{
    use Queueable, SerializesModels;

    public $errores;

    public function __construct(array $errores)
    {
        $this->errores = $errores;
    }

    public function build()
    {
        return $this->subject('❌ Error en la importación de contactos')
            ->view('emails.importacion_fallida')
            ->with('errores', $this->errores);
    }
}
