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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class SendTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:task {--scheduled}';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Envios masivos de mensajes programados';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Ejecutando tarea programada...');

        if ($this->option('scheduled')) {
            $tareasPendientes = TareaProgramada::where('status', 'pendiente')
                ->whereBetween('fecha_programada', [now()->subMinute(), now()->addMinute()])
                ->get();

            Log::info('Tareas programadas encontradas: ' . count($tareasPendientes));

            foreach ($tareasPendientes as $tarea) {
                $nombreArchivo = basename($tarea->numeros);
                $rutaArchivo = storage_path("app/tareas/$nombreArchivo");
                $payload = json_decode($tarea->payload, true);
                $mensajesEnviados = 0;

                try {
                    $rutaArchivo = realpath($rutaArchivo);

                    if ($rutaArchivo !== false && file_exists($rutaArchivo)) {
                        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                        foreach ($lineas as $linea) {
                            $userId = $this->obtenerUserIdDesdePhoneId($tarea->phone_id);
                            if ($userId) {
                                $contacto = $this->obtenerContacto($linea, $userId);

                                if ($contacto) {
                                    $personalizedBody = $this->reemplazarPlaceholders($tarea->body, $contacto);
                                    $payload['to'] = $linea;
                                    SendMessage::dispatch($tarea->token_app, $tarea->phone_id, $payload, $personalizedBody, $tarea->messageData, $tarea->distintivo)
                                        ->onQueue('whatsapp-queue');

                                    $mensajesEnviados++;
                                } else {
                                    Log::warning("Contacto no encontrado para el número: $linea");
                                }
                            } else {
                                Log::error("No se encontró un user_id para el phone_id: {$tarea->phone_id}");
                            }
                        }

                        if ($mensajesEnviados > 0) {
                            $this->registrarEnvio($payload['template']['name'], $mensajesEnviados, $tarea->body, $tarea->tag);
                        }
                    } else {
                        Log::error("El archivo no existe en la ruta: $rutaArchivo");
                    }
                } catch (\Exception $e) {
                    Log::error("Error al procesar la tarea programada: " . $e->getMessage());
                }

                if ($mensajesEnviados > 0) {
                    $tarea->status = 'enviada';
                    $tarea->save();
                } else {
                    Log::info("No se enviaron mensajes para la tarea {$tarea->id}, el estado no cambiará.");
                }
            }
        } else {
            $this->info('El comando debe ejecutarse solo cuando hay tareas programadas.');
        }

        $this->info('Tarea programada completada.');
    }





    /**
     * Obtener el user_id desde el phone_id.
     *
     * @param int $phoneId
     * @return int|null
     */
    protected function obtenerUserIdDesdePhoneId($phoneId)
    {
        $numero = DB::table('numeros')->where('id_telefono', $phoneId)->first();
        if ($numero) {
            $userNumero = DB::table('user_numeros')->where('numero_id', $numero->id)->first();
            return $userNumero ? $userNumero->user_id : null;
        }
        return null;
    }

    /**
     * Obtener el contacto por número de teléfono y usuario.
     *
     * @param string $telefono
     * @param int $userId
     * @return Contacto|null
     */
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

    /**
     * Reemplazar los placeholders en el cuerpo del mensaje.
     *
     * @param string $body
     * @param Contacto $contacto
     * @return string
     */
    protected function reemplazarPlaceholders($body, $contacto)
    {
        $customFieldValues = $contacto->customFieldValues->pluck('value', 'custom_field_id')->toArray();
        $customFields = CustomField::pluck('id', 'name')->toArray();

        foreach ($customFields as $fieldName => $fieldId) {
            $placeholder = '--' . $fieldName . '--';
            $value = $customFieldValues[$fieldId] ?? 'sin valor definido';
            $body = str_replace($placeholder, $value, $body);
        }

        return $body;
    }

    /**
     * Registrar el envío en la base de datos.
     *
     * @param string $nombrePlantilla
     * @param int $numeroDestinatarios
     * @param string $body
     * @param array $tags
     */
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
