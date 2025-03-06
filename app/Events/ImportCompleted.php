<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ImportCompleted implements ShouldBroadcast
{
    use SerializesModels;

    public $message; // 🔹 Propiedad pública para el mensaje

    public function __construct($message)
    {
        $this->message = $message; // 🔹 Almacenar el mensaje recibido
    }

    public function broadcastOn()
    {
        return new Channel('import-channel');
    }

    public function broadcastWith()
    {
        return ['message' => $this->message]; // 🔹 Enviar el mensaje en la transmisión
    }
}
