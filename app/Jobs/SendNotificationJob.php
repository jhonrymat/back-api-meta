<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Crea una nueva instancia del Job.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Ejecuta el Job.
     */
    public function handle(): void
    {
        try {
            // 1. Obtener el primer número asociado
            $numero = $this->user->numeros()->with('aplicacion')->first();

            // obtener el usuario
            $user = $this->user;

            if (!$numero || !$numero->aplicacion) {
                Log::error("❌ No se encontró número o aplicación para el usuario {$this->user->id}");
                return;
            }

            // Log::info("Numero completo {$numero->id}");

            // 2. Extraer ID y token
            $telefonoId = $numero->id_telefono;
            // Log::info("ID de teléfono: {$telefonoId}");
            $tokenApi = $numero->aplicacion->token_api;
            // Log::info("Token de API: {$tokenApi}");
            $version = 'v22.0';

            $payload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $user->phone,
                    'type' => 'template',
                    "template" => [
                        "name" => "envio_masivo_exitoso",
                        "language" => [
                            "code" => "es"
                        ],
                        "components" => [
                            [
                                "type" => "body",
                                "parameters" => [
                                    [
                                        "type" => "text",
                                        "text" => $user->name
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
            $message = Http::withToken($tokenApi)->post('https://graph.facebook.com/' . $version . '/' . $telefonoId . '/messages', $payload)->throw()->json();
                Log::info('Mensaje enviado correctamente: ', ['data' => $message]);
        } catch (\Throwable $e) {
            Log::error("❌ Excepción al enviar notificación: " . $e->getMessage());
        }
    }

}
