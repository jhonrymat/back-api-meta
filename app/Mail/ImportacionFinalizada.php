<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ImportacionFinalizada extends Mailable
{
    use Queueable, SerializesModels;
    public $omitidas;

    public function __construct($omitidas)
    {
        $this->omitidas = $omitidas;
    }

    public function build()
    {
        return $this->subject('Importación completada')
            ->view('emails.importacion_finalizada')
            ->with('omitidas', $this->omitidas);
    }
}
