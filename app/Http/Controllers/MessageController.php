<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Envio;
use App\Events\Webhook;
use App\Models\Message;
use App\Models\Numeros;
use App\Models\Reporte;
use App\Models\Contacto;
use PhpParser\Node\Expr;
use App\Jobs\SendMessage;
use Illuminate\Bus\Batch;
use App\Models\Distintivo;
use App\Libraries\Whatsapp;
use App\Models\CustomField;
use App\Models\UserContact;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Aplicaciones;
use Illuminate\Http\Request;
use App\Models\TareaProgramada;
use Illuminate\Validation\Rule;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ClocalController;
use Illuminate\Support\Facades\Validator;


class MessageController extends Controller
{
    public function NumbersApps()
    {
        $user = Auth::user();
        $aplicaciones = $user->aplicaciones()->with('numeros')->get();
        $distintivos = Distintivo::all();
        $tags = $user->tags()->with('contactos')->get();

        // Obtener los campos personalizados del usuario
        $customFields = $user->customFields()->pluck('name')->toArray();
        // Definir los campos predeterminados
        $defaultFields = ['nombre', 'apellido', 'correo', 'telefono', 'notas'];
        // Combinar los campos predeterminados y personalizados
        $availableFields = array_merge($defaultFields, $customFields);

        return view('plantillas/index', [
            'numeros' => $aplicaciones->pluck('numeros')->flatten(),
            'tags' => $tags,
            'distintivos' => $distintivos,
            'availableFields' => $availableFields,
        ]);
    }
    public function chat()
    {
        $user = Auth::user();

        // $numeros = Numeros::all();
        $numeros = $user->numeros()->with('aplicacion')->get();



        return view('chat/index', [
            'numeros' => $numeros
        ]);
    }
    public function index(Request $request)
    {
        $perPage = 20;

        $user = auth()->user(); // Obtener usuario autenticado

        // Base query: contactos del usuario logeado
        $query = $user->contactos()
            ->orderByDesc('tiene_mensajes_nuevos')
            ->orderByDesc('updated_at');

        // 🔍 Agregar filtro si viene el parámetro `search`
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Paginación
        $paginator = $query->simplePaginate($perPage);

        $contactos = $paginator->getCollection()->map(function ($contacto) {
            $data = $contacto->toArray();

            if ($contacto->tiene_mensajes_nuevos) {
                $ultimo = $contacto->messages()->latest('id')->first();
                if ($ultimo) {
                    $data['body'] = $ultimo->body;
                    $data['wa_id'] = $ultimo->wa_id;
                    $data['status'] = $ultimo->status;
                    $data['outgoing'] = $ultimo->outgoing;
                    $data['created_at'] = $ultimo->created_at;
                    $data['type'] = $ultimo->type;
                }
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $contactos,
            'nextPageUrl' => $paginator->nextPageUrl(),
        ]);
    }





    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'wa_id' => ['required', 'max:20'],
                'body' => ['required', 'string'],
            ]);

            $input = $request->all();
            $wp = new Whatsapp();
            $response = $wp->sendText($input['wa_id'], $input['body'], $input['id_phone'], $input['token_api']);

            $message = new Message();
            $message->wa_id = $input['wa_id'];
            $message->wam_id = $response["messages"][0]["id"];
            $message->phone_id = $input['id_phone'];
            $message->type = 'text';
            $message->outgoing = true;
            $message->body = $input['body'];
            $message->status = 'sent';
            $message->caption = '';
            $message->data = '';
            $message->save();



            return response()->json([
                'success' => true,
                'data' => $message,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al enviar mensaje: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function show($waId, Request $request)
    {
        $phone_id = $request->input('id_phone');
        $perPage = 10; // Define cuántos mensajes quieres cargar por página

        try {
            // Obtener los mensajes paginados
            $messagesQuery = DB::table('messages as m')
                ->where('wa_id', $waId)
                ->where('m.phone_id', $phone_id)
                ->orderByDesc('created_at') // Ordena por created_at descendente para obtener los más recientes primero
                ->simplePaginate($perPage);

            $messages = $messagesQuery->getCollection();

            // Procesar cada mensaje si es necesario
            $messages->transform(function ($message) {
                if ($message->type == 'template') {
                    $message->data = unserialize($message->data);
                }
                return $message;
            });

            // Agrupar los mensajes por la fecha de 'created_at'
            $grouped = $messages->groupBy(function ($item) {
                return Carbon::parse($item->created_at)->format('Y-m-d'); // Agrupa por fecha
            });
            // Ordena los mensajes dentro de cada grupo por 'created_at' de manera descendente
            $grouped = $grouped->map(function ($dayMessages) {
                return $dayMessages->sortBy(function ($message) {
                    return Carbon::parse($message->created_at)->timestamp;
                });
            });

            // Obtener el nombre del contacto relacionado
            $contacto = Contacto::where('telefono', $waId)->first();

            if ($contacto) {
                $contacto->tiene_mensajes_nuevos = false;
                $contacto->save();
            }

            return response()->json([
                'success' => true,
                'contacto' => $contacto,
                'data' => $grouped,
                'nextPageUrl' => $messagesQuery->nextPageUrl(), // Proporciona la URL para cargar la próxima página de mensajes
                'prevPageUrl' => $messagesQuery->previousPageUrl(), // Proporciona la URL para la página anterior (si la necesitas)
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al obtener mensajes del chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function update(Request $request, Message $message)
    {
        //
    }

    public function destroy(Message $message)
    {
        //
    }

    public function sendReport($id_report)
    {
        // obtener id_telefono del reporte
        $reporte = Reporte::findOrFail($id_report);
        $telefono = $reporte->id_telefono;

        // obtener el número y su aplicación relacionada
        $numero = Numeros::where('id_telefono', $telefono)->with('aplicacion', 'users')->firstOrFail();
        $aplicacion = $numero->aplicacion;
        $user = $numero->users()->first(); // puede haber varios usuarios, aquí se toma el primero

        if (!$aplicacion || !$user) {
            Log::warning("No se encontró aplicación o usuario para el teléfono {$telefono}");
            return;
        }

        try {
            $token = $aplicacion->token_api;
            $phoneId = $telefono;
            $version = 'v22.0';
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $user->phone,
                'type' => 'template',
                "template" => [
                    "name" => "reporte_mensual",
                    "language" => [
                        "code" => "es"
                    ],
                    "components" => [
                        [
                            "type" => "header",
                            "parameters" => [
                                [
                                    "type" => "text",
                                    "text" => $user->name

                                ]
                            ]
                        ],
                        [
                            "type" => "button",
                            'index' => '0',
                            "sub_type" => "url",
                            "parameters" => [
                                [
                                    "type" => "text",
                                    "text" => $id_report
                                ]
                            ]
                        ],
                    ]
                ]
            ];
            $message = Http::withToken($token)->post('https://graph.facebook.com/' . $version . '/' . $phoneId . '/messages', $payload)->throw()->json();
            Log::info('Mensaje enviado correctamente: ', ['data' => $message]);

        } catch (Exception $e) {
            Log::error('Error al enviar mensaje de prueba a jhon: ' . $e->getMessage());
        }
    }



    public function sendMessages($plantilla)
    {
        try {
            // Verificar si el usuario está autenticado
            if (Auth::check()) {
                // Obtener el usuario autenticado
                $user = Auth::user();
                // Acceder a la información del usuario

                $token = env('WHATSAPP_API_TOKEN');
                $phoneId = env('WHATSAPPI_API_PHONE_ID');
                $version = 'v22.0';
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $user->phone,
                    'type' => 'template',
                    "template" => [
                        "name" => "finalizacion_de_envio",
                        "language" => [
                            "code" => "es"
                        ],
                        "components" => [
                            [
                                "type" => "header",
                                "parameters" => [
                                    [
                                        "type" => "text",
                                        "text" => $user->name

                                    ]
                                ]
                            ],
                            [
                                "type" => "body",
                                "parameters" => [
                                    [
                                        "type" => "text",
                                        "text" => $plantilla
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                $message = Http::withToken($token)->post('https://graph.facebook.com/' . $version . '/' . $phoneId . '/messages', $payload)->throw()->json();
                Log::info('Mensaje enviado correctamente: ', ['data' => $message]);
            }
        } catch (Exception $e) {
            Log::error('Error al enviar mensaje de finalizacion de envio: ' . $e->getMessage());
        }
    }

    public function verifyWebhook(Request $request)
    {
        try {
            $verifyToken = env('WHATSAPP_VERIFY_TOKEN');
            $query = $request->query();

            $mode = $query['hub_mode'];
            $token = $query['hub_verify_token'];
            $challenge = $query['hub_challenge'];

            if ($mode && $token) {
                if ($mode === 'subscribe' && $token == $verifyToken) {
                    return response($challenge, 200)->header('Content-Type', 'text/plain');
                }
            }

            throw new Exception('Invalid request');
        } catch (Exception $e) {
            Log::error('Error al verificar el webhook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function processWebhook(Request $request)
    {
        try {
            $bodyContent = json_decode($request->getContent(), true);
            $body = '';

            // Determine what happened...
            $value = $bodyContent['entry'][0]['changes'][0]['value'];

            if (!empty($value['statuses'])) {
                $status = $value['statuses'][0]['status']; // sent, delivered, read, failed
                $wam = Message::where('wam_id', $value['statuses'][0]['id'])->first();

                if (!empty($wam->id)) {
                    $wam->status = $status;
                    $wam->save();
                    Webhook::dispatch($wam, true);
                }
                // Si el estado es 'failed', procesar y registrar los detalles del error
                if ($status == 'failed') {
                    $errorMessage = $value['statuses'][0]['errors'][0]['message'] ?? 'Unknown error';
                    $errorCode = $value['statuses'][0]['errors'][0]['code'] ?? 'Unknown code';
                    $errorDetails = $value['statuses'][0]['errors'][0]['error_data']['details'] ?? 'No additional details';

                    // Registrar el error en los logs de Laravel
                    Log::error("Webhook processing error: {$errorMessage}, Code: {$errorCode}, Details: {$errorDetails}");

                    // Aquí podrías agregar lógica adicional si necesitas manejar estos errores de manera específica
                    // Por ejemplo, notificar al equipo de soporte, realizar reintento condicional, etc.
                    if (!empty($wam->id)) {
                        $wam->caption = $errorCode;
                        $wam->save();
                        Webhook::dispatch($wam, true);
                    }
                }
            } else if (!empty($value['messages'])) { // Message
                $exists = Message::where('wam_id', $value['messages'][0]['id'])->first();

                if (empty($exists->id)) {

                    // Verificar si el contacto existe
                    $contacto = Contacto::where('telefono', $value['contacts'][0]['wa_id'])->first();
                    // Si no existe, crearlo
                    if (!$contacto) {
                        $contacto = new Contacto();
                        $contacto->telefono = $value['contacts'][0]['wa_id'];
                        $contacto->nombre = $value['contacts'][0]['profile']['name'];
                        $contacto->notas = "Contacto creado automáticamente por webhook";
                        $contacto->save();

                        // Asociar los tags seleccionados al nuevo contacto
                        // Versión segura (no duplica) con pivot:
                        $contacto->tags()->syncWithoutDetaching([
                            22 => ['user_id' => auth()->id()],
                        ]);
                    } else if ($contacto->nombre == $contacto->telefono) {
                        $contacto->nombre = $value['contacts'][0]['profile']['name'];
                        $contacto->save();
                    }
                    $mediaSupported = ['audio', 'document', 'image', 'video', 'sticker'];

                    if ($value['messages'][0]['type'] == 'text') {
                        $message = $this->_saveMessage(
                            $value['messages'][0]['text']['body'],
                            'text',
                            $value['messages'][0]['from'],
                            $value['messages'][0]['id'],
                            $value['metadata']['phone_number_id'],
                            $value['messages'][0]['timestamp']
                        );

                        Webhook::dispatch($message, false);
                    } else if (in_array($value['messages'][0]['type'], $mediaSupported)) {
                        $mediaType = $value['messages'][0]['type'];
                        $mediaId = $value['messages'][0][$mediaType]['id'];
                        $wp = new Whatsapp();
                        //consulta para traer token
                        $num = Numeros::where('id_telefono', $value['metadata']['phone_number_id'])->first();

                        $app = Aplicaciones::where('id', $num->aplicacion_id)->first();

                        $tk = $app->token_api;

                        //fin de consulta
                        $file = $wp->downloadMedia($mediaId, $tk);

                        $caption = null;
                        if (!empty($value['messages'][0][$mediaType]['caption'])) {
                            $caption = $value['messages'][0][$mediaType]['caption'];
                        }

                        if (!is_null($file)) {
                            $message = $this->_saveMessage(
                                env('APP_URL') . '/storage/' . $file,
                                $mediaType,
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['metadata']['phone_number_id'],
                                $value['messages'][0]['timestamp'],
                                $caption
                            );
                            Webhook::dispatch($message, false);
                        }
                    } else {
                        $type = $value['messages'][0]['type'];
                        if (!empty($value['messages'][0][$type])) {
                            $message = $this->_saveMessage(
                                "($type): \n _" . serialize($value['messages'][0][$type]) . "_",
                                'other',
                                $value['messages'][0]['from'],
                                $value['messages'][0]['id'],
                                $value['metadata']['phone_number_id'],
                                $value['messages'][0]['timestamp']
                            );
                        }
                        Webhook::dispatch($message, false);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $body,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al procesar el webhook: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function loadMessageTemplates(Request $request)
    {
        try {
            $wp = new Whatsapp();
            $token = $request->query('token_api');
            $waba_id = $request->query('id_c_business');
            $templates = $wp->loadTemplates($token, $waba_id);

            return response()->json([
                'success' => true,
                'data' => $templates['data'],
            ], 200);
        } catch (Exception $e) {
            Log::error('Error al cargar las plantillas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        // Límites en KB (puedes moverlos a config/whatsapp.php)
        $maxImageKB = config('whatsapp.max_image_kb', 5120);    // 5 MB
        $maxVideoKB = config('whatsapp.max_video_kb', 16384);   // 16 MB
        $maxDocKB = config('whatsapp.max_document_kb', 10240); // 10 MB

        $type = strtoupper($request->input('type', 'DOCUMENT'));
        $file = $request->file('file') ?? $request->file('pdf'); // compatibilidad

        // Arma reglas por tipo
        $rulesByType = [
            'DOCUMENT' => ['required', 'file', 'mimetypes:application/pdf', "max:$maxDocKB"],
            'IMAGE' => ['required', 'file', 'mimetypes:image/jpeg,image/png', "max:$maxImageKB"],
            // WhatsApp Cloud acepta mp4 y 3gpp; deja solo mp4 si prefieres
            'VIDEO' => ['required', 'file', 'mimetypes:video/mp4,video/3gpp,application/mp4', "max:$maxVideoKB"],
        ];
        $labels = [
            'DOCUMENT' => 'documento (PDF)',
            'IMAGE' => 'imagen (JPG o PNG)',
            'VIDEO' => 'video (MP4 o 3GP)',
        ];
        $human = fn(int $kb) => rtrim(rtrim(number_format($kb / 1024, 2), '0'), '.') . ' MB';

        // Validador con mensajes personalizados
        $validator = Validator::make(
            ['type' => $type, 'file' => $file],
            [
                'type' => ['required', Rule::in(['DOCUMENT', 'IMAGE', 'VIDEO'])],
                'file' => $rulesByType[$type] ?? $rulesByType['DOCUMENT'],
            ],
            [
                'type.in' => 'Tipo no válido. Usa DOCUMENT, IMAGE o VIDEO.',
                'file.required' => 'Debes seleccionar un archivo.',
                'file.file' => 'El archivo es inválido o está corrupto.',
                'file.mimetypes' => "Formato no permitido. Para {$labels[$type]} solo se acepta ese formato.",
                'file.max' => "El {$labels[$type]} supera el tamaño máximo de " .
                    ($type === 'DOCUMENT' ? $human($maxDocKB) :
                        ($type === 'IMAGE' ? $human($maxImageKB) : $human($maxVideoKB))) . '.',
            ]
        );

        if ($validator->fails()) {
            // Devuelve 422 con el primer mensaje claro para el frontend
            return response()->json([
                'ok' => false,
                'errors' => $validator->errors(),
                'message' => $validator->errors()->first('file') ?? $validator->errors()->first(),
            ], 422);
        }

        try {
            $folder = match ($type) {
                'IMAGE' => 'headers/image',
                'VIDEO' => 'headers/video',
                default => 'headers/document',
            };

            $ext = $file->getClientOriginalExtension();
            $filename = $folder . '/' . uniqid() . '.' . $ext;
            $file->storeAs('', $filename, 'public');

            return response()->json([
                'ok' => true,
                'url' => Storage::disk('public')->url($filename),
            ], 200);

        } catch (Throwable $e) {
            Log::error('Error uploading header: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => 'Error al subir el archivo.'], 500);
        }
    }


    public function sendMessageTemplate(Request $request)
    {
        $user = Auth::user();
        try {
            $input = $request->all();
            $wp = new Whatsapp();
            $templateName = $input['template_name'];
            $templateLang = $input['template_language'];
            $tokenApp = $input['token_api'];
            $phone_id = $input['phone_id'];
            $waba_id_app = $input['id_c_business'];
            $fechaProgramada = $input['programar'];
            $distintivo = $input['distintivoSelect'];
            $tags = !empty($input['selectedTags']) ? $input['selectedTags'] : [22];
            $template = $wp->loadTemplateByName($templateName, $templateLang, $tokenApp, $waba_id_app);
            // Inicializar la colección de trabajos
            $jobs = collect();
            if (!$template) {
                throw new Exception("Invalid template or template not found.");
            }

            $templateBody = '';
            foreach ($template['components'] as $component) {
                if ($component['type'] == 'BODY') {
                    $templateBody = $component['text'];
                }
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'type' => 'template',
                "template" => [
                    "name" => $templateName,
                    "language" => [
                        "code" => $templateLang
                    ]
                ]
            ];

            $messageData = [];
            if (!empty($input['header_type']) && !empty($input['header_url'])) {
                $type = strtolower($input['header_type']);
                if ($type == 'document') {
                    $payload['template']['components'][] = [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => $type,
                                $type => [
                                    "filename" => "Contrato.pdf",
                                    'link' => $input['header_url'],
                                ]
                            ]
                        ],
                    ];
                } else {
                    $payload['template']['components'][] = [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => $type,
                                $type => [
                                    'link' => $input['header_url'],
                                ]
                            ]
                        ],
                    ];
                }
                $messageData = [
                    'header_type' => $input['header_type'],
                    'header_url' => $input['header_url'],
                ];
            }

            $recipients = explode("\n", $input['recipients']);
            $contacts = Contacto::with('customFieldValues')->whereIn('telefono', array_map(function ($recipient) {
                return (int) filter_var($recipient, FILTER_SANITIZE_NUMBER_INT);
            }, $recipients))->get()->keyBy('telefono');

            // Obtener los campos personalizados
            $customFields = CustomField::pluck('id', 'name')->toArray();

            // ⚡️ **🔹 Responde inmediatamente antes de procesar los mensajes**
            response()->json([
                'success' => true,
                'message' => 'Se ha creado el envió con éxito.',
            ], 200)->send();
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }


            if ($fechaProgramada !== null) {
                $fechaFormateada = Carbon::parse($fechaProgramada)->toDateTimeString();
                $numeros = $input['recipients'];
                $fechaHoraActual = Carbon::now()->format('Ymd_His');
                $rutaArchivo = "tareas/tarea_{$fechaHoraActual}.txt";
                Storage::put($rutaArchivo, $numeros);


                // Reemplazar placeholders {{}} con los valores proporcionados
                $personalizedBody = $templateBody;

                if (!empty($input['body_placeholders'])) {
                    $bodyParams = [];
                    foreach ($input['body_placeholders'] as $key => $placeholder) {

                        $bodyParams[] = ['type' => 'text', 'text' => $placeholder];

                        $personalizedBody = str_replace('{{' . ($key + 1) . '}}', $placeholder, $personalizedBody);
                    }
                }

                $payload['template']['components'][1] = [
                    'type' => 'body',
                    'parameters' => $bodyParams,
                ];

                $tarea = TareaProgramada::create([
                    'token_app'       => $tokenApp,
                    'phone_id'        => $phone_id,
                    'numeros'         => $rutaArchivo,
                    'payload'         => json_encode($payload),
                    'body'            => $personalizedBody,
                    'messageData'     => json_encode($messageData),
                    'status'          => 'pendiente',
                    'fecha_programada'=> $fechaFormateada,
                    'tag'             => $tags,
                    'distintivo'      => $distintivo,
                ]);

                $user->tareasProgramadas()->attach($tarea->id);

                return response()->json([
                    'success' => true,
                    'data' => ' Mensajes agregado al cron correctamente.',
                ], 200);
            } else {
                $banderaArrayBody = null; // Inicializar la variable
                foreach ($recipients as $recipient) {
                    if (isset($payload['template']['components'])) {
                        $components = $payload['template']['components'];

                        foreach ($components as $index => $component) {
                            if (Arr::get($component, 'type') === 'header') {
                                // conocer ubicacion del body
                                $banderaArrayBody = 1;
                            }
                        }
                    }

                    // Verificar que $banderaArrayBody esté definida
                    if ($banderaArrayBody === null) {
                        $banderaArrayBody = 0; // Asignar un valor predeterminado si no se encontró un header
                    }


                    $phone = (int) filter_var($recipient, FILTER_SANITIZE_NUMBER_INT);
                    $contacto = $contacts->get($phone);

                    if (!$contacto) {
                        // Si el contacto no existe en el mapa, buscar en la base de datos
                        $contacto = Contacto::where('telefono', $phone)->first();

                        if (!$contacto) {
                            // Si el contacto no existe en la base de datos, crear uno nuevo
                            $contacto = new Contacto();
                            $contacto->nombre = $phone;
                            $contacto->telefono = $phone;
                            $contacto->notas = "Contacto creado automáticamente por colas";
                            $contacto->save();
                        }

                        // Asociar el contacto con el usuario actual en user_contacts
                        if (!$user->contactos->contains($contacto->id)) {
                            $userContact = new UserContact();
                            $userContact->user_id = $user->id;
                            $userContact->contacto_id = $contacto->id;
                            $userContact->save();
                        }

                        // Asociar tags si existen
                        // $tags puede venir vacío o con IDs (strings o ints)
                        if (!empty($tags)) {
                            $tagIds = collect((array) $tags)
                                ->filter(fn($v) => $v !== null && $v !== '')
                                ->map(fn($id) => (int) $id)
                                ->unique();

                            $contacto->tags()->syncWithoutDetaching(
                                $tagIds->mapWithKeys(fn($id) => [$id => ['user_id' => $user->id]])->toArray()
                            );
                        }
                    }


                    // Obtener los valores de los campos personalizados del contacto
                    $customFieldValues = $contacto->customFieldValues->pluck('value', 'custom_field_id')->toArray();

                    // Reemplazar placeholders {{}} con los valores proporcionados
                    $personalizedBody = $templateBody;

                    if (!empty($input['body_placeholders'])) {
                        $bodyParams = [];
                        foreach ($input['body_placeholders'] as $key => $placeholder) {

                            if (Str::startsWith($placeholder, '--') && Str::endsWith($placeholder, '--')) {
                                $fieldName = substr($placeholder, 2, -2);
                                $fieldId = $customFields[$fieldName] ?? null;
                                $placeholder = $contacto->$fieldName ?? ($customFieldValues[$fieldId] ?? 'sin valor definido');
                            }
                            $bodyParams[] = ['type' => 'text', 'text' => $placeholder];

                            $personalizedBody = str_replace('{{' . ($key + 1) . '}}', $placeholder, $personalizedBody);
                        }

                        // Llenar la parte que limpiaste
                        $payload['template']['components'][$banderaArrayBody] = [
                            'type' => 'body',
                            'parameters' => $bodyParams,
                        ];
                    }

                    $payload['to'] = $phone;

                    // Limpiar y llenar la parte del button si existe
                    if (!empty($input['buttons_url'])) {
                        $payload['template']['components'][2] = [
                            'type' => 'button',
                            'index' => '0',
                            'sub_type' => 'url',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $input['buttons_url'],
                                ]
                            ],
                        ];
                    }
                    // 👇 Agrega el job a la colección en lugar de despacharlo directamente
                    $jobs->push(
                        new SendMessage($tokenApp, $phone_id, $payload, $personalizedBody, $messageData, $distintivo)
                    );
                }
                // 🔒 Guardar envío con transacción
                $envio = DB::transaction(function () use ($templateName, $recipients, $personalizedBody, $tags, $user) {
                    $envio = new Envio();
                    $envio->nombrePlantilla = $templateName;
                    $envio->numeroDestinatarios = count($recipients);
                    $envio->status = 'Pendiente';
                    $envio->body = $personalizedBody;
                    $envio->tag = $tags;
                    $envio->save();

                    $user->envios()->syncWithoutDetaching([$envio->id]);

                    return $envio;
                });


                // 👇 Despacha el batch y guarda el ID

                // 🚀 Despachar batch con configuraciones optimizadas
                $batch = Bus::batch($jobs)
                    ->name("Envío Masivo: {$templateName} ({$envio->id})")
                    ->then(function (Batch $batch) use ($envio, $user) {
                        // ✅ Actualizar usando query builder (más rápido que Eloquent)
                        DB::table('envios')
                            ->where('id', $envio->id)
                            ->update([
                                'status' => 'Completado',
                                'updated_at' => now()
                            ]);

                        Log::info("✅ Batch completado", [
                            'batch_id' => $batch->id,
                            'envio_id' => $envio->id
                            // ❌ NO incluir $batch->processedJobs(), $batch->failedJobs
                        ]);
                    })
                    ->catch(function (Batch $batch, Throwable $e) use ($envio) {
                        DB::table('envios')
                            ->where('id', $envio->id)
                            ->update([
                                'status' => 'Completado con errores',
                                'updated_at' => now()
                            ]);

                        Log::error("❌ Batch con errores", [
                            'batch_id' => $batch->id,
                            'envio_id' => $envio->id,
                            'error' => $e->getMessage()
                            // ❌ NO incluir $batch->failedJobs
                        ]);
                    })
                    ->finally(function (Batch $batch) use ($envio) {
                        // 📊 Log final con estadísticas
                        Log::info("📊 Batch finalizado", [
                            'batch_id' => $batch->id,
                            'envio_id' => $envio->id
                        ]);
                    })
                    ->onQueue('whatsapp-queue')
                    ->allowFailures() // ⚠️ IMPORTANTE: No cancelar todo el batch si algunos jobs fallan
                    ->dispatch();

                // 💾 Actualizar batch_id
                $envio->batch_id = $batch->id;
                $envio->save();

                Log::info('envio encolado ' . count($recipients));

                // Actualiza status_send aquí si aplica
                try {
                    if (!empty($input['status_send'])) {
                        app(ClocalController::class)->update($input['solicitudId'], $input['status_send']);
                    }
                } catch (Throwable $e) {
                    Log::warning('No se pudo actualizar status_send', ['e' => $e->getMessage()]);
                }

                if (!empty($input['status_send'])) {
                    $clocalController = new ClocalController();
                    $clocalController->update($input['solicitudId'], $input['status_send']);
                }
                return response()->json([
                    'success' => true,
                    'message' => $envio->id . " - Envío encolado correctamente.",
                    'batch_id' => $batch->id,
                    'total_recipients' => count($recipients)
                ], 202); // <- una sola respuesta y listo
            }


        } catch (Exception $e) {
            Log::error('Error en sendMessageTemplate: ' . $e->getMessage(), [
                'input' => $input,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function _saveMessage($message, $messageType, $waId, $wamId, $phoneId, $timestamp = null, $caption = null, $data = '')
    {
        $wam = new Message();
        $wam->body = $message;
        $wam->outgoing = false;
        $wam->type = $messageType;
        $wam->wa_id = $waId;
        $wam->wam_id = $wamId;
        $wam->phone_id = $phoneId;
        $wam->status = 'sent';
        $wam->caption = $caption;
        $wam->data = $data;

        if (!is_null($timestamp)) {
            $wam->created_at = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
            $wam->updated_at = Carbon::createFromTimestamp($timestamp)->toDateTimeString();
        }
        $wam->save();

        Webhook::dispatch($wam, false);
        Log::info('mensaje enviado por el usuario: ' . $wam->body);
        // encontrar el contacto relacionado
        $contacto = Contacto::where('telefono', $waId)->first();
        // 🔥 Marcar contacto con mensaje nuevo
        $contacto->tiene_mensajes_nuevos = true;
        $contacto->save();

        return $wam;
    }

    public function getEnvioStatus($id)
    {
        try {
            $envio = Envio::findOrFail($id);

            // Verificar que el usuario tenga acceso
            if (!auth()->user()->envios->contains($envio->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Si tiene batch_id, obtener info del batch
            $batchInfo = null;
            if ($envio->batch_id) {
                try {
                    $batch = Bus::findBatch($envio->batch_id);

                    if ($batch) {
                        $batchInfo = [
                            'total' => $batch->totalJobs,
                            'processed' => $batch->processedJobs(),
                            'pending' => $batch->pendingJobs,
                            'failed' => $batch->failedJobs,
                            'progress' => $batch->progress(),
                            'finished' => $batch->finished(),
                            'cancelled' => $batch->cancelled(),
                        ];
                    }
                } catch (Exception $e) {
                    Log::warning("No se pudo obtener info del batch: {$e->getMessage()}");
                }
            }

            // Respuesta
            return response()->json([
                'success' => true,
                'envio' => [
                    'id' => $envio->id,
                    'nombre_plantilla' => $envio->nombrePlantilla,
                    'status' => $envio->status,
                    'numero_destinatarios' => $envio->numeroDestinatarios,
                    'batch_id' => $envio->batch_id,
                    'created_at' => $envio->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $envio->updated_at->format('Y-m-d H:i:s'),
                ],
                'batch' => $batchInfo,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Envío no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error("Error obteniendo status de envío: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado del envío'
            ], 500);
        }
    }

    public function monitorearEnvio($id)
    {
        $envio = Envio::findOrFail($id);

        // Verificar autorización
        if (!auth()->user()->envios->contains($envio->id)) {
            abort(403, 'No autorizado');
        }

        return view('envios.monitor', compact('envio'));
    }

    /**
     * API: Reintentar mensajes fallidos de un envío
     * Ruta: POST /envios/{id}/reintentar
     */
    public function reintentarEnvio($id)
    {
        try {
            // Buscar el envío
            $envio = Envio::findOrFail($id);

            // Verificar autorización
            $user = auth()->user();
            if (!$user->envios->contains($envio->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para reintentar este envío'
                ], 403);
            }

            // Verificar que tenga batch_id
            if (!$envio->batch_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este envío no tiene batch asociado'
                ], 400);
            }

            // Buscar jobs fallidos relacionados con este envío
            $failedJobs = DB::table('failed_jobs')
                ->where(function ($query) use ($envio) {
                    $query->where('payload', 'like', "%{$envio->batch_id}%")
                        ->orWhere('payload', 'like', "%distintivo\":\"{$envio->distintivo}%");
                })
                ->limit(100) // Límite de seguridad
                ->get();

            if ($failedJobs->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay jobs fallidos para reintentar',
                    'reintentados' => 0
                ]);
            }

            // Reintentar cada job
            $reintentados = 0;
            $errores = 0;

            foreach ($failedJobs as $failedJob) {
                try {
                    // Usar el comando de Laravel para reintentar
                    Artisan::call('queue:retry', ['id' => $failedJob->uuid]);
                    $reintentados++;
                } catch (Exception $e) {
                    $errores++;
                    Log::error("Error reintentando job {$failedJob->uuid}: {$e->getMessage()}");
                }
            }

            // Actualizar estado del envío
            if ($reintentados > 0) {
                DB::table('envios')
                    ->where('id', $envio->id)
                    ->update([
                        'status' => 'Pendiente',
                        'updated_at' => now()
                    ]);

                Log::info("Envío reintentado", [
                    'envio_id' => $envio->id,
                    'reintentados' => $reintentados,
                    'errores' => $errores,
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Jobs reintentados correctamente",
                'reintentados' => $reintentados,
                'errores' => $errores,
                'envio_id' => $envio->id
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Envío no encontrado'
            ], 404);

        } catch (Exception $e) {
            Log::error("Error reintentando envío {$id}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al reintentar el envío'
            ], 500);
        }
    }

    /**
     * OPCIONAL: Ver detalles de jobs fallidos
     * Ruta: GET /envios/{id}/fallidos
     */
    public function verJobsFallidos($id)
    {
        try {
            $envio = Envio::findOrFail($id);

            // Verificar autorización
            if (!auth()->user()->envios->contains($envio->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            // Buscar jobs fallidos
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', "%{$envio->batch_id}%")
                ->select('id', 'uuid', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit(50)
                ->get();

            // Extraer números de teléfono del payload
            $failedJobs = $failedJobs->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $command = unserialize($payload['data']['command'] ?? '');

                return [
                    'uuid' => $job->uuid,
                    'telefono' => $command->payload['to'] ?? 'N/A',
                    'error' => substr($job->exception, 0, 200),
                    'fecha' => $job->failed_at
                ];
            });

            return response()->json([
                'success' => true,
                'failed_jobs' => $failedJobs,
                'total' => $failedJobs->count()
            ]);

        } catch (Exception $e) {
            Log::error("Error obteniendo jobs fallidos: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener jobs fallidos'
            ], 500);
        }
    }

}
