<?php

namespace App\Jobs;

use App\Events\ImportCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyUserOfCompletedImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Crear una nueva instancia del Job.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Ejecutar el Job.
     */
    public function handle()
    {
        Log::info('🚀 Importación completada. Enviando evento de Pusher...');

        // 🔹 Disparar el evento que Pusher escuchará
        event(new ImportCompleted("La importación para {$this->user->name} ha finalizado."));
    }
}
