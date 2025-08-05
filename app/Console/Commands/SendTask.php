<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Envio;
use App\Models\Contacto;
use App\Jobs\SendMessage;
use App\Models\CustomField;
use App\Models\TareaProgramada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendTask extends Command
{
    protected $signature = 'send:task {--scheduled}';
    protected $description = 'Envios masivos de mensajes programados';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Ejecutando tarea programada...');

        if ($this->option('scheduled')) {
            $tareasPendientes = TareaProgramada::where('fecha_programada', '<=', now())
                ->where('status', 'pendiente')
                ->get();

            // ⚡️ Optimización: cargar campos personalizados una sola vez
            $customFields = CustomField::pluck('id', 'name')->toArray();

            foreach ($tareasPendientes as $tarea) {
                $nombreArchivo = basename($tarea->numeros);
                $rutaArchivo = storage_path("app/tareas/$nombreArchivo");
                $payload = json_decode($tarea->payload, true);

                try {
                    $rutaArchivo = realpath($rutaArchivo);

                    if ($rutaArchivo !== false && file_exists($rutaArchivo)) {
                        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                        foreach ($lineas as $linea) {
                            $userId = $this->obtenerUserIdDesdePhoneId($tarea->phone_id);
                            if ($userId) {
                                $contacto = $this->obtenerContacto($linea, $userId);

                                if ($contacto) {
                                    // Reemplazar placeholders en texto visible
                                    $personalizedBody = $this->reemplazarPlaceholders($tarea->body, $contacto, $customFields);

                                    // Buscar placeholders en el body
                                    preg_match_all('/--(.*?)--/', $tarea->body, $matches);

                                    if (!empty($matches[1])) {
                                        foreach ($payload['template']['components'] as &$component) {
                                            if ($component['type'] === 'body' && isset($component['parameters'])) {
                                                foreach ($component['parameters'] as &$param) {
                                                    if ($param['type'] === 'text' && preg_match('/--(.*?)--/', $param['text'], $match)) {
                                                        $fieldName = $match[1];
                                                        $value = null;

                                                        $fieldId = $customFields[$fieldName] ?? null;
                                                        if ($fieldId) {
                                                            $value = $contacto->customFieldValues->where('custom_field_id', $fieldId)->first()->value ?? null;
                                                        }

                                                        if (is_null($value) && isset($contacto->$fieldName)) {
                                                            $value = $contacto->$fieldName;
                                                        }

                                                        $param['text'] = $value ?? 'sin valor definido';
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }


                                    $payload['to'] = $linea;

                                    SendMessage::dispatch(
                                        $tarea->token_app,
                                        $tarea->phone_id,
                                        $payload,
                                        $personalizedBody,
                                        $tarea->messageData,
                                        $tarea->distintivo
                                    )->onQueue('whatsapp-queue');
                                } else {
                                    Log::warning("Contacto no encontrado para el número: $linea");
                                }
                            } else {
                                Log::error("No se encontró un user_id para el phone_id: {$tarea->phone_id}");
                            }
                        }

                        $this->registrarEnvio($payload['template']['name'], count($lineas), $tarea->body, $tarea->tag);
                    } else {
                        Log::error("El archivo no existe en la ruta: $rutaArchivo");
                    }
                } catch (\Exception $e) {
                    Log::error("Error al procesar la tarea programada: " . $e->getMessage());
                }

                $tarea->status = 'enviada';
                $tarea->save();
            }
        } else {
            $this->info('El comando debe ejecutarse solo cuando hay tareas programadas.');
        }

        $this->info('Tarea programada completada.');
    }

    protected function obtenerUserIdDesdePhoneId($phoneId)
    {
        $numero = DB::table('numeros')->where('id_telefono', $phoneId)->first();
        if ($numero) {
            $userNumero = DB::table('user_numeros')->where('numero_id', $numero->id)->first();
            return $userNumero ? $userNumero->user_id : null;
        }
        return null;
    }

    protected function obtenerContacto($telefono, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            Log::error("User not found for ID: $userId");
            return null;
        }

        return $user->contactos()
            ->where('telefono', (int) filter_var($telefono, FILTER_SANITIZE_NUMBER_INT))
            ->first();
    }

    protected function reemplazarPlaceholders($body, $contacto, $customFields)
    {
        $customFieldValues = $contacto->customFieldValues->pluck('value', 'custom_field_id')->toArray();

        foreach ($customFields as $fieldName => $fieldId) {
            $placeholder = '--' . $fieldName . '--';

            // ✅ Si hay valor en campos personalizados, úsalo
            if (isset($customFieldValues[$fieldId])) {
                $value = $customFieldValues[$fieldId];
            }
            // ✅ Si el campo es también una propiedad del contacto, úsala
            elseif (isset($contacto->$fieldName) && !is_null($contacto->$fieldName)) {
                $value = $contacto->$fieldName;
            }
            // ❌ En última instancia, si no hay nada...
            else {
                $value = 'sin valor definido';
            }

            $body = str_replace($placeholder, $value, $body);
        }

        return $body;
    }


    protected function registrarEnvio($nombrePlantilla, $numeroDestinatarios, $body, $tags)
    {
        $envio = new Envio();
        $envio->nombrePlantilla = $nombrePlantilla;
        $envio->numeroDestinatarios = $numeroDestinatarios;
        $envio->status = 'Completado';
        $envio->body = $body;
        $envio->tag = $tags;
        $envio->save();
    }
}
