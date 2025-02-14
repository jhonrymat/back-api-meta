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
        Log::info('🔹 Iniciando send:task --scheduled');

        // 1️⃣ Verificar la fecha del sistema
        Log::info('🕒 Fecha actual del servidor: ' . now()->toDateTimeString());

        // 2️⃣ Verificar cuántas tareas existen en la base de datos
        $todasLasTareas = TareaProgramada::all();
        Log::info('📋 Total de tareas en la base de datos: ' . count($todasLasTareas));

        // 3️⃣ Filtrar solo las tareas pendientes que deben ejecutarse
        $tareasPendientes = TareaProgramada::whereRaw("fecha_programada <= ?", [now()])
            ->where('status', 'pendiente')
            ->get();

        Log::info('📌 Tareas encontradas para ejecutar: ' . count($tareasPendientes));

        foreach ($tareasPendientes as $tarea) {
            Log::info("📢 Procesando tarea ID: {$tarea->id}, programada para: {$tarea->fecha_programada}");

            try {
                // 4️⃣ Obtener el archivo asociado a la tarea
                $nombreArchivo = basename($tarea->numeros);
                $rutaArchivo = storage_path("app/tareas/$nombreArchivo");

                if (!file_exists($rutaArchivo)) {
                    Log::error("❌ ERROR: Archivo no encontrado en la ruta: $rutaArchivo");
                    continue;
                }

                // 5️⃣ Leer el archivo y obtener los números de teléfono
                $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                Log::info("📄 Archivo cargado correctamente, contiene " . count($lineas) . " números.");

                // 6️⃣ Decodificar el payload del mensaje
                $payload = json_decode($tarea->payload, true);

                foreach ($lineas as $linea) {
                    $userId = $this->obtenerUserIdDesdePhoneId($tarea->phone_id);

                    if (!$userId) {
                        Log::error("❌ ERROR: No se encontró un user_id para phone_id: {$tarea->phone_id}");
                        continue;
                    }

                    $contacto = $this->obtenerContacto($linea, $userId);

                    if (!$contacto) {
                        Log::warning("⚠️ Advertencia: Contacto no encontrado para el número: $linea");
                        continue;
                    }

                    // 7️⃣ Personalizar el cuerpo del mensaje con los datos del contacto
                    $personalizedBody = $this->reemplazarPlaceholders($tarea->body, $contacto);
                    $payload['to'] = $linea;

                    // 8️⃣ Enviar mensaje a la cola correcta
                    SendMessage::dispatch($tarea->token_app, $tarea->phone_id, $payload, $personalizedBody, $tarea->messageData, $tarea->distintivo)
                        ->onQueue('whatsapp-queue'); // 🔹 Asegura que se envía a la cola correcta
                    Log::info("🚀 Mensaje enviado a la cola 'whatsapp-queue' para: $linea");
                }

                // 9️⃣ Registrar el envío en la base de datos
                $this->registrarEnvio($payload['template']['name'], count($lineas), $tarea->body, $tarea->tag);
                Log::info("✅ Registro de envío guardado en la base de datos.");

                // 🔟 Actualizar estado de la tarea a "enviada"
                $tarea->status = 'enviada';
                $tarea->save();
                Log::info("✅ Tarea ID: {$tarea->id} marcada como 'enviada' en la base de datos.");

            } catch (\Exception $e) {
                Log::error("❌ ERROR al procesar la tarea ID: {$tarea->id} - " . $e->getMessage());
            }
        }

        Log::info('✅ Finalizando send:task --scheduled');
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
