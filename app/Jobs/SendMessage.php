<?php

namespace App\Jobs;

use Exception;
use App\Models\Message;
use App\Libraries\Whatsapp;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $payload;
    public $body;
    public $messageData;
    public $tokenApp;
    public $phone_id;
    public $distintivo;

    // ⚡ CRÍTICO: Configurar reintentos y timeout
    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 2;

    // ⚡ Backoff exponencial: 10s, 30s, 90s
    public $backoff = [10, 30, 90];

    public function __construct($tokenApp, $phone_id, $payload, $body, $messageData = [], $distintivo)
    {
        $this->payload = $payload;
        $this->body = $body;
        $this->messageData = $messageData;
        $this->tokenApp = $tokenApp;
        $this->phone_id = $phone_id;
        $this->distintivo = $distintivo;
    }

    public function handle()
    {
        // 🛑 Si el batch fue cancelado, no continuar
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::info("Job cancelado porque el batch fue cancelado");
            return;
        }

        try {
            $wp = new Whatsapp();
            $request = $wp->genericPayload($this->payload, $this->tokenApp, $this->phone_id);

            if (isset($request["contacts"][0]["wa_id"])) {
                // ✅ ÉXITO: Usar transacción con lock para evitar duplicados
                $this->saveSuccessMessage($request);
            } else {
                // ❌ ERROR: Procesar error de WhatsApp
                $this->saveErrorMessage($request);
            }

        } catch (Exception $e) {
            Log::error('Error en SendMessage Job: ' . $e->getMessage(), [
                'payload_to' => $this->payload['to'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            // 🔄 Lanzar excepción para que Laravel reintente el job
            throw $e;
        }
    }

    /**
     * Guardar mensaje exitoso con protección contra duplicados
     */
    private function saveSuccessMessage($request)
    {
        $wamId = $request["messages"][0]["id"];

        // 🔒 Usar updateOrCreate para evitar duplicados (requiere índice único en wam_id)
        DB::transaction(function () use ($request, $wamId) {
            Message::updateOrCreate(
                ['wam_id' => $wamId], // Buscar por wam_id
                [
                    'body' => $this->body,
                    'outgoing' => true,
                    'type' => 'template',
                    'wa_id' => $request["contacts"][0]["wa_id"],
                    'phone_id' => $this->phone_id,
                    'status' => 'sent',
                    'caption' => '',
                    'data' => serialize($this->messageData),
                    'distintivo' => $this->distintivo,
                    'code' => '',
                ]
            );
        });

        // Log::info("✅ Mensaje enviado exitosamente", [
        //     'wam_id' => $wamId,
        //     'wa_id' => $request["contacts"][0]["wa_id"]
        // ]);
    }

    /**
     * Guardar mensaje con error
     */
    private function saveErrorMessage($request)
    {
        $jsonStartPos = strpos($request, '{');
        if ($jsonStartPos === false) {
            Log::error("Respuesta de error no contiene JSON válido: " . substr($request, 0, 200));
            return;
        }

        $errorJsonString = substr($request, $jsonStartPos);
        $errorJson = json_decode($errorJsonString, true);

        if (!$errorJson || !isset($errorJson['error']['code'], $errorJson['error']['fbtrace_id'])) {
            Log::error("Error al decodificar respuesta de error: " . $errorJsonString);
            return;
        }

        $errorCode = $errorJson['error']['code'];
        $fbtrace_id = $errorJson['error']['fbtrace_id'];

        // 🔒 Guardar error con transacción
        DB::transaction(function () use ($errorCode, $fbtrace_id, $errorJsonString) {
            Message::create([
                'body' => $this->body,
                'outgoing' => true,
                'type' => 'template',
                'wa_id' => $this->payload["to"],
                'wam_id' => $fbtrace_id,
                'phone_id' => $this->phone_id,
                'status' => 'failed',
                'caption' => $errorJsonString,
                'data' => serialize($this->messageData),
                'distintivo' => $this->distintivo,
                'code' => $errorCode,
            ]);
        });

        Log::warning("⚠️ Mensaje falló", [
            'wa_id' => $this->payload["to"],
            'error_code' => $errorCode,
            'fbtrace_id' => $fbtrace_id
        ]);
    }

    /**
     * Manejar fallos del job después de todos los reintentos
     */
    public function failed(Exception $exception)
    {
        Log::error('❌ SendMessage Job falló definitivamente después de todos los reintentos', [
            'payload_to' => $this->payload['to'] ?? 'unknown',
            'exception' => $exception->getMessage(),
        ]);
    }
}
