<?php

namespace App\Http\Controllers;

use Log;
use Exception;
use App\Models\Bot;
use OpenAI\Factory;
use App\Models\Aplicaciones;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class BotController extends Controller
{

    public function index(Request $request)
    {
        // Obtener el usuario logueado
        $user = Auth::user();

        if (!$user) {
            return redirect('login')->with('error', 'Debe estar logueado para ver las aplicaciones.');
        }

        // Obtener todas las aplicaciones del usuario con sus bots asociados
        $aplicaciones = $user->aplicaciones()->with('bot')->get();
        // Obtener todos los bots del usuario
        $todosLosBots = $user->bots;
        // Obtener todas las aplicaciones
        $aplicaciones2 = Aplicaciones::all();

        ;

        foreach ($todosLosBots as $bot) {
            try {
                // Cambiar dinámicamente las credenciales de OpenAI
                $openAI = (new Factory())
                    ->withApiKey($bot->openai_key)
                    ->withOrganization($bot->openai_org)
                    ->withHttpHeader('OpenAI-Beta', 'assistants=v2') // Agregar el encabezado necesario
                    ->make();
                $assistant = $openAI->assistants()->retrieve($bot->openai_assistant);
                $bot->model = $assistant->model; // Guardar temporalmente el modelo en el objeto bot
            } catch (Exception $e) {
                Log::error("Error al recuperar el modelo del asistente {$bot->id}: " . $e->getMessage());
                $bot->model = null; // Si falla, asignamos null
            }
        }

        return view('bots/index', compact('aplicaciones', 'todosLosBots', 'aplicaciones2'));
    }



    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required',
            'openai_key' => 'required',
            'openai_org' => 'required',
            'openai_assistant' => 'required',
            'aplicacion_id' => 'nullable|exists:aplicaciones,id', // Cambiar a nullable
        ]);

        $user = Auth::user();

        // Crear un nuevo bot
        $bot = Bot::create([
            'user_id' => $user->id,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'openai_key' => $request->openai_key,
            'openai_org' => $request->openai_org,
            'openai_assistant' => $request->openai_assistant,
        ]);

        // Asociar el nuevo bot con la aplicación solo si aplicacion_id está presente
        if ($request->filled('aplicacion_id')) {
            $aplicacion = Aplicaciones::find($request->aplicacion_id);

            // Verificar si la aplicación ya tiene un bot asociado y desasociarlo
            $botAnterior = $aplicacion->bot()->first();
            if ($botAnterior) {
                $aplicacion->bot()->detach($botAnterior->id); // Desasociar el bot anterior si existe
            }

            $aplicacion->bot()->attach($bot->id); // Asociar el nuevo bot con la aplicación
        }

        return response()->json([
            'success' => 'Bot creado con éxito.',
            'data' => $bot
        ]);
    }


    // metodo editar bot
    public function edit($id)
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401); // 401 Unauthorized
        }

        $bot = Bot::findOrFail($id);

        // Obtener la primera aplicación asociada (si existe)
        $aplicacion_id = $bot->aplicaciones->isNotEmpty() ? $bot->aplicaciones->first()->id : null;

        return response()->json([
            'success' => 'Bot recuperado con éxito.',
            'data' => [
                'id' => $bot->id,
                'nombre' => $bot->nombre,
                'descripcion' => $bot->descripcion,
                'openai_key' => $bot->openai_key,
                'openai_org' => $bot->openai_org,
                'openai_assistant' => $bot->openai_assistant,
                'aplicacion_id' => $aplicacion_id, // Incluir aplicacion_id en la respuesta
                'permitir_imagenes' => $bot->permitir_imagenes
            ]
        ]);

    }

    public function update(Request $request, $id)
    {
        // Modelos que admiten imágenes
        $modosQueAdmitenImagenes = [
            "gpt-4.5-preview",
            "gpt-4o",
            "gpt-4o-mini",
            "gpt-4-turbo",
            "o1"
        ];

        $request->validate([
            'nombre' => 'required',
            'descripcion' => 'required',
            'openai_key' => 'required',
            'openai_org' => 'required',
            'openai_assistant' => 'required',
            'aplicacion_id' => 'nullable|exists:aplicaciones,id', // Cambiado a nullable
            'allow_images' => 'nullable|boolean',
        ]);

        $bot = Bot::findOrFail($id);

        // Validar si el modelo seleccionado admite imágenes
        if ($request->allow_images && !in_array($request->model, $modosQueAdmitenImagenes)) {
            return response()->json([
                'error' => 'El modelo seleccionado no admite imágenes. Modelos que sí las admiten: ' . implode(", ", $modosQueAdmitenImagenes)
            ], 400);
        }

        // Actualizar los datos del bot
        $bot->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'openai_key' => $request->openai_key,
            'openai_org' => $request->openai_org,
            'openai_assistant' => $request->openai_assistant,
            'permitir_imagenes' => $request->allow_images ? 1 : 0, // Guardar como 1 o 0
        ]);

        // Si `aplicacion_id` está presente, manejamos la asociación
        if ($request->filled('aplicacion_id')) {
            $aplicacion = Aplicaciones::find($request->aplicacion_id);

            // Buscar si esta aplicación ya está asociada a otro bot
            $botAnterior = $aplicacion->bot()->first();
            if ($botAnterior && $botAnterior->id !== $bot->id) {
                // Desasociar la aplicación del bot anterior
                $aplicacion->bot()->detach($botAnterior->id);
            }

            // Asociar la aplicación seleccionada al bot actual
            $aplicacion->bot()->sync([$bot->id]);
        } else {
            // Si `aplicacion_id` no está presente, eliminar la asociación de la aplicación existente (si la hay)
            $bot->aplicaciones()->detach();
        }

        // Actualizar en OpenAI
        $openAI = (new Factory())
            ->withApiKey($bot->openai_key)
            ->withOrganization($bot->openai_org)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        $openAI->assistants()->modify($bot->openai_assistant, [
            'name' => $request->nombre,
            'instructions' => $request->instructions,
            'model' => $request->model,
            'temperature' => floatval($request->temperature), // Convertir a decimal
            'top_p' => floatval($request->top_p), // Convertir a decimal
        ]);

        return response()->json([
            'success' => 'Bot actualizado con éxito.',
            'data' => $bot
        ]);
    }




    public function destroy(Request $request, $id)
    {
        try {
            // Buscar el bot por ID
            $bot = Bot::findOrFail($id);

            // Verificar si el bot pertenece a una de las aplicaciones del usuario autenticado
            if (Auth::user()->bots->contains($bot)) {
                // Obtener la opción de eliminación seleccionada
                $deleteOption = $request->input('deleteOption');

                if ($deleteOption === 'both') {
                    // Eliminar en OpenAI
                    $openAI = (new Factory())
                        ->withApiKey($bot->openai_key)
                        ->withOrganization($bot->openai_org)
                        ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
                        ->make();

                    $response = $openAI->assistants()->delete($bot->openai_assistant);

                    // Verificar si se eliminó con éxito en OpenAI
                    if (!$response->deleted) {
                        return response()->json([
                            'error' => 'No se pudo eliminar el bot en OpenAI.'
                        ], 500);
                    }
                }

                // Eliminar el bot de la base de datos
                $bot->delete();

                return response()->json([
                    'success' => 'Bot eliminado con éxito.'
                ]);
            } else {
                return response()->json([
                    'error' => 'No tienes permiso para eliminar este bot.'
                ], 403);
            }
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            // Capturar errores específicos de OpenAI y devolver el mensaje al frontend
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        } catch (Exception $e) {
            // Capturar otros errores generales
            return response()->json([
                'error' => 'Ocurrió un error al intentar eliminar el bot.'
            ], 500);
        }
    }




    // moetodo para crear bot con asistente openai
    public function createBot(Request $request)
    {
        try {

            $modosQueAdmitenImagenes = [
                "gpt-4.5-preview",
                "gpt-4o",
                "gpt-4o-mini",
                "gpt-4-turbo",
                "o1"
            ];

            $uploadedFile = null;
            $fileIds = []; // Inicializamos la lista de IDs de los archivos subidos

            $request->validate([
                'nombre' => 'required',
                'descripcion' => 'required',
                'archivos' => 'array',
                'archivos.*' => 'mimetypes:text/x-c,text/x-c++,text/x-csharp,text/css,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/x-golang,text/html,text/x-java,text/javascript,application/json,text/markdown,application/pdf,text/x-php,application/vnd.openxmlformats-officedocument.presentationml.presentation,text/x-python,text/x-script.python,text/x-ruby,application/x-sh,text/x-tex,application/typescript,text/plain',
                'openai_key' => 'required',
                'openai_org' => 'required',
                'instrucciones' => 'required',
                'model' => 'required',
                'temperature' => 'required|numeric',
                'top_p' => 'required|numeric',
                'aplicacion_id' => 'nullable|exists:aplicaciones,id',
                'allow_images' => 'boolean',
            ]);

            // Validar si el usuario intenta activar imágenes en un modelo que no lo permite
            if ($request->allow_images && !in_array($request->model, $modosQueAdmitenImagenes)) {
                return response()->json([
                    'error' => 'El modelo seleccionado no admite imágenes. Modelos que sí las admiten: ' . implode(", ", $modosQueAdmitenImagenes)
                ], 400);
            }

            $nombreCarpeta = $request->nombre;

            // Crear una instancia personalizada de OpenAI con las credenciales del usuario y el encabezado requerido
            $openAI = (new Factory())
                ->withApiKey($request->openai_key)
                ->withOrganization($request->openai_org)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2') // Agregar el encabezado necesario
                ->make();

            // Procesar los archivos
            if ($request->hasfile('archivos')) {
                foreach ($request->file('archivos') as $archivo) {
                    // Define la carpeta de destino
                    $rutaDestino = 'uploads/' . $nombreCarpeta . '/'; // Puedes cambiar esto a la ruta que prefieras

                    // Crear un nombre único para cada archivo
                    $nombreArchivo = time() . '-' . $archivo->getClientOriginalName();

                    // Mover el archivo a la carpeta especificada
                    $archivo->move(public_path($rutaDestino), $nombreArchivo);

                    $uploadedFile = $openAI->files()->upload([
                        'file' => fopen($rutaDestino . $nombreArchivo, 'r'),  // Abre el archivo como un stream
                        'purpose' => 'assistants',
                    ]);

                    // Agregar el ID del archivo subido a la lista de file_ids
                    $fileIds[] = $uploadedFile->id;


                }
                //creacion de vector store
                $vector = $openAI->vectorStores()->create([
                    'file_ids' => $fileIds,
                    'name' => 'vector-store-' . $nombreCarpeta,
                ]);

                // Crear el asistente utilizando todos los IDs de los archivos subidos
                $assistant = $openAI->assistants()->create([
                    'name' => $request->nombre,
                    'tools' => [
                        [
                            'type' => 'file_search',
                        ],
                    ],
                    'tool_resources' => [
                        'file_search' => [
                            'vector_store_ids' => [$vector->id],
                        ],
                    ],
                    'instructions' => $request->instrucciones,
                    'model' => $request->model,
                    'temperature' => floatval($request->temperature), // Convertir a decimal
                    'top_p' => floatval($request->top_p), // Convertir a decimal

                ]);
            }

            // Crear el asistente utilizando todos los IDs de los archivos subidos
            $assistant = $openAI->assistants()->create([
                'name' => $request->nombre,
                'instructions' => $request->instrucciones,
                'model' => $request->model,
                'temperature' => floatval($request->temperature), // Convertir a decimal
                'top_p' => floatval($request->top_p), // Convertir a decimal

            ]);

            // Verifica que $assistant se haya creado correctamente antes de asignar openai_assistant
            if (!$assistant || !isset($assistant->id)) {
                return response()->json(['error' => 'No se pudo crear el asistente de OpenAI.'], 400);
            }

            // Obtener la aplicación
            $user = Auth::user();
            // Crear un nuevo bot
            $bot = Bot::create([
                'user_id' => $user->id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'openai_key' => $request->openai_key,
                'openai_org' => $request->openai_org,
                'openai_assistant' => $assistant->id,
                'permitir_imagenes' => $request->allow_images ? 1 : 0, // Guardar estado
            ]);

            // Asociar el bot con la aplicación solo si se seleccionó una aplicación
            if ($request->filled('aplicacion_id')) {
                $aplicacion = Aplicaciones::findOrFail($request->aplicacion_id);
                $botAnterior = $aplicacion->bot()->first();
                if ($botAnterior) {
                    $aplicacion->bot()->detach($botAnterior->id);
                }
                $aplicacion->bot()->attach($bot->id);
            }

            return response()->json([
                'success' => 'Bot creado con éxito y asociado a la aplicación.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error al guardar asistente: ' . $e->getMessage()
            ], 500);
        }

    }


    // consultar asistente de openai
    public function BotOpenai($id)
    {
        $bot = Bot::findOrFail($id);

        // Verificar si el bot pertenece al usuario autenticado
        if (Auth::user()->bots->contains($bot)) {
            $openAI = (new Factory())
                ->withApiKey($bot->openai_key)
                ->withOrganization($bot->openai_org)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2') // Agregar el encabezado necesario
                ->make();
            $data = $openAI->assistants()->retrieve($bot->openai_assistant);

            return response()->json([
                'success' => 'Asistente recuperado con éxito.',
                'data' => $data
            ]);
        } else {
            return response()->json([
                'error' => 'El usuario no tiene acceso a este asistente.'
            ], 403);
        }
    }


    public function guardarWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url'
        ]);

        $bot = Bot::findOrFail($request->bot_id);

        if (!$bot) {
            return response()->json(['message' => 'Bot no encontrado'], 404);
        }

        $bot->update(['webhook_url' => $request->webhook_url]);

        return response()->json(['message' => 'Webhook guardado correctamente']);
    }

    public function permitirImagenes($botId)
    {
        $bot = Bot::find($botId);

        if (!$bot) {
            return response()->json(['error' => 'Bot no encontrado'], 404);
        }

        return response()->json(['permitirImagenes' => (bool) $bot->permitir_imagenes]);

    }
}
