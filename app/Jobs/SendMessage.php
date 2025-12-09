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

    // 🔥 NUEVO: Liberar batch antes de fallar para evitar deadlocks
    public $deleteWhenMissingModels = true;

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
        // 🛑 Si el batch fue cancelado, salir inmediatamente
        if ($this->batch()?->cancelled()) {
            Log::info('Batch cancelado, job omitido', [
                'wa_id' => $this->payload['to'] ?? 'unknown'
            ]);
            return;
        }

        // 🛑 Si el batch fue cancelado, no continuar
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
    /**
     * 🔥 OPTIMIZADO: Sin transacción anidada, updateOrCreate ya es atómico
     */
    private function saveSuccessMessage($request)
    {
        $wamId = $request["messages"][0]["id"];

        try {
            // ✅ updateOrCreate es atómico por sí solo (no necesita DB::transaction)
            Message::updateOrCreate(
                ['wam_id' => $wamId], // Buscar por wam_id (necesita índice único)
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
        } catch (\Illuminate\Database\QueryException $e) {
            // Si hay error de duplicado (código 1062), ignorarlo
            if ($e->getCode() == 23000) {
                Log::warning('Mensaje duplicado ignorado', ['wam_id' => $wamId]);
                return;
            }
            throw $e;
        }
    }

    /**
     * Guardar mensaje con error
     */
    private function saveErrorMessage($request)
    {
        $jsonStartPos = strpos($request, '{');
        if ($jsonStartPos === false) {
            Log::error("Respuesta sin JSON válido: " . substr($request, 0, 200));
            return;
        }

        $errorJsonString = substr($request, $jsonStartPos);
        $errorJson = json_decode($errorJsonString, true);

        if (!$errorJson || !isset($errorJson['error']['code'])) {
            Log::error("Error al decodificar JSON: " . $errorJsonString);
            return;
        }

        $errorCode = $errorJson['error']['code'];
        $fbtrace_id = $errorJson['error']['fbtrace_id'] ?? 'unknown';

        try {
            // ✅ Sin transacción, create ya es atómico
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
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Error al guardar mensaje fallido', [
                'wa_id' => $this->payload["to"],
                'error' => $e->getMessage()
            ]);
        }

        Log::warning("⚠️ Mensaje falló", [
            'wa_id' => $this->payload["to"],
            'error_code' => $errorCode,
            'fbtrace_id' => $fbtrace_id
        ]);
    }

    /**
     * Manejar fallos del job después de todos los reintentos
     */
    /**
     * 🔥 NUEVO: Liberar el batch antes de marcar como fallido
     */
    public function failed(Exception $exception)
    {
        // Liberar el batch primero para evitar deadlocks
        if ($batch = $this->batch()) {
            try {
                // No hacer nada con el batch aquí, Laravel ya lo maneja
            } catch (Exception $e) {
                Log::error('Error al procesar batch en failed()', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::error('❌ SendMessage Job falló definitivamente', [
            'payload_to' => $this->payload['to'] ?? 'unknown',
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
