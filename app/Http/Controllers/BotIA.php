<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use OpenAI\Factory;
use App\Models\Lead;
use App\Models\User;
use App\Models\Thread;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use OpenAI\Responses\Threads\Runs\ThreadRunResponse;

class BotIA extends Controller
{
    // No utilizamos los tipos aquí para versiones anteriores a PHP 7.4
    public $question;
    public $answer;
    public $error;

    public function __construct()
    {
        $this->question = null; // Asignar valores predeterminados manualmente
        $this->answer = null;
        $this->error = null;
    }


    // Método para manejar preguntas

    public function askBot(Request $request)
    {
        try {
            $botId = $request->input('botId');  // Obtener el botId enviado desde el frontend
            if (!$botId) {
                return response()->json(['error' => 'El botId es obligatorio.'], 400);
            }

            // obtener el bot desde la base de datos
            $bot = Bot::find($botId);
            if (!$bot) {
                return response()->json(['error' => 'El bot no existe.'], 404);
            }
            // obterner user autenticado
            $user = Auth::user();
            if (!$user || !$user->phone) {
                return response()->json(['error' => 'No se encontró el número de teléfono del usuario.'], 400);
            }

            $question = $request->input('question', '');
            $imageUrl = null;

            // 🔹 Si el usuario envió una imagen, guardarla y obtener la URL pública
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $imageUrl = asset('storage/' . $imagePath);
                Log::info('Imagen guardada en: ' . $imageUrl);
            }

            // 🔹 Si hay imagen y texto, procesar ambos
            if ($imageUrl) {
                $botResponse = $this->processImageAndText($imageUrl, $question, $botId, $bot->openai_key, $bot->openai_org, $bot->openai_assistant, $user->phone);
            } elseif (!empty($question)) {
                // 🔹 Si solo hay texto, procesarlo normalmente
                $botResponse = $this->ask($question, $user->phone, $botId, $bot->openai_key, $bot->openai_org, $bot->openai_assistant);
            } else {
                return response()->json(['error' => 'Debes enviar una pregunta o una imagen.'], 400);
            }
            // Llamar a la función ask para obtener la respuesta del bot

            return response()->json([
                'answer' => $botResponse,  // Devolver la respuesta del bot en formato JSON
            ]);
        } catch (\Exception $e) {
            Log::error('Error en askBot: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al procesar la solicitud.'], 500);
        }

    }

    private function processImageAndText($imageUrl, $question, $botId, $openai_key, $openai_org, $openai_assistant, $waId)
    {
        try {
            $openAI = (new Factory())
                ->withApiKey($openai_key)
                ->withOrganization($openai_org)
                ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
                ->make();

            // Verificar que la URL sea accesible
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                Log::error('URL inválida generada para la imagen: ' . $imageUrl);
                return 'Error: No se pudo generar una URL válida para la imagen.';
            }
            $bot = Bot::find($botId);
            // Verificar si la imagen es accesible
            $imageHeaders = @get_headers($imageUrl);
            if (!$imageHeaders || strpos($imageHeaders[0], '200') === false) {
                Log::error('OpenAI no puede acceder a la imagen: ' . $imageUrl);
                return 'Error: OpenAI no puede acceder a la imagen.';
            }

            // Buscar o crear un thread
            $thread = Thread::where('wa_id', $waId)->where('bot_id', $botId)->first();
            if (!$thread) {
                $threadRun = $this->createAndRunThread($openai_key, $openai_org, $openai_assistant);
                $thread = Thread::create([
                    'wa_id' => $waId,
                    'thread_id' => $threadRun->threadId,
                    'bot_id' => $botId,
                ]);
            }

            // Enviar mensaje con imagen y texto al asistente
            $messageResponse = $openAI->threads()->messages()->create(
                threadId: $thread->thread_id,
                parameters: [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $question ?: 'Describe esta imagen.'],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ],
                ]
            );

            if (!$messageResponse) {
                throw new \Exception('Error al enviar el mensaje con imagen.');
            }

            // Ejecutar el asistente
            $run = $openAI->threads()->runs()->create(
                threadId: $thread->thread_id,
                parameters: [
                    'assistant_id' => $bot->openai_assistant,
                ]
            );

            if (!$run) {
                throw new \Exception('Error al ejecutar el asistente.');
            }

            // Esperar respuesta de OpenAI con un timeout extendido
            $timeout = 60; // Aumentado a 60 segundos
            $elapsed = 0;

            do {
                sleep(2);
                $elapsed += 2;
                $runStatus = $openAI->threads()->runs()->retrieve($thread->thread_id, $run->id);

                if ($elapsed >= $timeout) {
                    throw new \Exception('Timeout al procesar la imagen.');
                }
            } while (in_array($runStatus->status, ['queued', 'in_progress']));

            if ($runStatus->status !== 'completed') {
                throw new \Exception('Error al procesar la imagen con el asistente.');
            }

            // Obtener la respuesta final del asistente
            $messages = $openAI->threads()->messages()->list($thread->thread_id);

            if (!isset($messages->data[0]->content[0]->text->value)) {
                throw new \Exception('No se recibió respuesta.');
            }

            Log::info('Respuesta del asistente: ' . $messages->data[0]->content[0]->text->value);

            return $messages->data[0]->content[0]->text->value;

        } catch (\Exception $e) {
            Log::error('Error en processImageAndText: ' . $e->getMessage());
            return 'Ocurrió un error al procesar la imagen: ' . $e->getMessage();
        }
    }






    public function handleFunctionCall($functionName, $parameters, $botId)
    {
        try {
            // Use firstOrCreate to avoid duplicate entry issues
            Lead::firstOrCreate(
                ['email' => $parameters['email']], // Unique field to check
                [
                    'bot_id' => $botId,
                    'nombre' => $parameters['nombre'],
                    'telefono' => $parameters['telefono'],
                    'detalles' => $parameters['detalles'],
                    'calificacion' => $parameters['calificacion'],
                    'estado' => 'nuevo',
                ]
            );

            return 'tus datos han sido guardados correctamente';

        } catch (\Illuminate\Database\QueryException $exception) {
            \Log::error('Failed to create lead: ' . $exception->getMessage());
            // Additional handling if needed, like returning a specific response
        }

        return 'Ha ocurrido un error al guardar tus datos';
    }

    public function CreatePrompt($functionName, $parameters, $botId)
    {
        try {
            $prompt = $parameters['prompt'];

            return $prompt;

        } catch (\Illuminate\Database\QueryException $exception) {
            \Log::error('Error al crear el prompt: ' . $exception->getMessage());
        }

        return response()->json([
            'type' => 'error',
            'content' => 'Ha ocurrido un error al guardar tus datos'
        ]);
    }


    public function ask($question, $waId, $botId, $openai_key, $openai_org, $openai_assistant)
    {
        $this->question = $question;

        // Obtener el bot y verificar si tiene un webhook habilitado
        $bot = Bot::find($botId);
        $webhookUrl = $bot->webhook_url ?? null;

        // Buscar si ya existe un hilo para este usuario y bot específico
        $thread = Thread::where('wa_id', $waId)
            ->where('bot_id', $botId)
            ->first();

        if (!$thread) {
            // Crear un nuevo hilo con OpenAI y guardarlo en la base de datos
            $threadRun = $this->createAndRunThread($openai_key, $openai_org, $openai_assistant);
            $thread = Thread::create([
                'wa_id' => $waId,
                'thread_id' => $threadRun->threadId,
                'bot_id' => $botId,
            ]);
        }

        if ($webhookUrl) {
            Log::info('Webhook configurado para este bot.');
            // 🔹 Si hay un webhook configurado, enviar la solicitud a n8n
            try {
                $response = Http::post($webhookUrl, [
                    'message' => $question,
                    'thread_id' => $thread->thread_id,
                ]);

                Log::info('Respuesta de n8n:', $response->json());

                // Procesar la respuesta de n8n
                $n8nResponse = $response->json();
                $this->answer = $n8nResponse['answer'] ?? 'Lo siento, no entendí tu mensaje.';
            } catch (\Exception $e) {
                Log::error('Error al enviar solicitud a n8n: ' . $e->getMessage());
                $this->answer = 'Hubo un problema al procesar tu mensaje.';
            }
        } else {
            // 🔹 Si NO hay un webhook, usar OpenAI directamente
            // Si existe un hilo, usar el hilo existente
            $threadRun = $this->continueThread($thread->thread_id, $openai_key, $openai_org, $openai_assistant);
            return $this->loadAnswer($threadRun, $openai_key, $openai_org, $openai_assistant, $botId);
        }

        return $this->answer;
    }

    // Método para crear y ejecutar un nuevo hilo
    private function createAndRunThread($openai_key, $openai_org, $openai_assistant)
    {
        // Cambiar dinámicamente las credenciales de OpenAI
        config(['openai.api_key' => $openai_key]);
        config(['openai.organization' => $openai_org]);

        return Openai::threads()->createAndRun([
            'assistant_id' => $openai_assistant,
            'thread' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->question,
                    ],
                ],
            ],
        ]);

    }

    // Método para continuar un hilo existente
    private function continueThread($threadId, $openai_key, $openai_org, $openai_assistant)
    {
        // Cambiar dinámicamente las credenciales de OpenAI
        config(['openai.api_key' => $openai_key]);
        config(['openai.organization' => $openai_org]);

        $respuesta = OpenAI::threads()->messages()->create(
            $threadId,
            [
                'role' => 'user',
                'content' => $this->question,
            ]
        );

        return OpenAI::threads()->runs()->create(
            $threadId,
            [
                'assistant_id' => $openai_assistant,
            ]
        );
    }

    // Método para cargar la respuesta desde el hilo
    private function loadAnswer($threadRun, $openai_key, $openai_org, $openai_assistant, $botId)
    {
        // Cambiar dinámicamente las credenciales de OpenAI
        config(['openai.api_key' => $openai_key]);
        config(['openai.organization' => $openai_org]);


        // Limitar el número de intentos para evitar bucles infinitos
        $maxAttempts = 10;
        $attempts = 0;

        if (!$threadRun || !isset($threadRun->status)) {
            Log::error('OpenAI Request failed: No se pudo obtener el estado del threadRun.');
            return 'Error al comunicarse con OpenAI. Intenta más tarde.';
        }


        while (in_array($threadRun->status, ['queued', 'in_progress']) && $attempts < $maxAttempts) {
            sleep(2);
            $threadRun = OpenAI::threads()->runs()->retrieve($threadRun->threadId, $threadRun->id);
            $attempts++;
        }

        if ($threadRun->status !== 'completed' && $threadRun->status !== 'requires_action') {
            Log::error('OpenAI Request failed, status: ' . $threadRun->status);
            return 'Error en la generación de respuesta. Intenta de nuevo.';
        }

        $isResponsePrompt = false;
        $dataResponse = '';
        if (isset($threadRun->status) && $threadRun->status === 'requires_action') {
            $tools_to_call = $threadRun->requiredAction->submitToolOutputs->toolCalls ?? [];
            $tools_output_array = []; // Initialize outside the loop

            foreach ($tools_to_call as $tool_call) {
                if ($tool_call->function->name === 'create_lead') {
                    $respuesta = $this->handleFunctionCall($tool_call->function->name, json_decode($tool_call->function->arguments, true), $botId);
                    $tools_output_array = [
                        'tool_outputs' => [
                            [
                                'tool_call_id' => $tool_call->id,  // Cambiado de 'tools_call_id' a 'tool_call_id'
                                'output' => $respuesta,
                            ],
                        ]
                    ];
                } else if ($tool_call->function->name === 'get_prompt_response') {
                    $prompt = $this->CreatePrompt($tool_call->function->name, json_decode($tool_call->function->arguments, true), $botId);
                    $isResponsePrompt = true;
                    $dataResponse = $prompt;
                    $tools_output_array = [
                        'tool_outputs' => [
                            [
                                'tool_call_id' => $tool_call->id,  // Cambiado de 'tools_call_id' a 'tool_call_id'
                                'output' => 'Prompt creado correctamente',
                            ],
                        ]
                    ];
                }
            }
            // Submit all tool outputs at once after the loop
            if (!empty($tools_output_array)) {
                // Pasar como un objeto, no como un arreglo
                OpenAI::threads()->runs()->submitToolOutputs(
                    $threadRun->threadId,
                    $threadRun->id,
                    $tools_output_array
                );

                $attempts = 0;
                while (in_array($threadRun->status, ['completed', 'failed', 'requires_action']) && $attempts < $maxAttempts) {
                    // Recupera el estado actual de la tarea
                    $threadRun = OpenAI::threads()->runs()->retrieve(
                        $threadRun->threadId,
                        $threadRun->id
                    );
                    // Espera 10 segundos antes de la próxima verificación
                    sleep(5);
                    $attempts++;
                }

                // Imprime el estado final después de que se complete el proceso
                \Log::info("Estado final de la tarea: " . $threadRun->status);
            }
        }

        $messageList = OpenAI::threads()->messages()->list(
            $threadRun->threadId,
        );

        if ($isResponsePrompt) {
            // Si la respuesta es un prompt, guardar una cadena con el identificador
            Log::info('Respuesta generada como prompt: ' . $dataResponse);
            $this->answer = 'PROMPT:' . $dataResponse;
        } else {
            // Si no, guardar el mensaje normal
            $answer = $messageList->data[0]->content[0]->text->value ?? null;

            if (!$answer) {
                Log::warning('No se recibió respuesta de OpenAI.');
                return 'Lo siento, no tengo una respuesta en este momento.';
            }

            return $answer;
        }
    }

    public function askBotForEmbed(Request $request)
    {
        try {
            $botId = $request->input('botId');
            if (!$botId) {
                return response()->json(['error' => 'El botId es obligatorio.'], 400);
            }

            // Obtener el bot desde la base de datos
            $bot = Bot::find($botId);
            if (!$bot) {
                return response()->json(['error' => 'El bot no existe.'], 404);
            }

            // Validar que se recibió una pregunta
            $question = $request->input('question');
            if (!$question) {
                return response()->json(['error' => 'La pregunta es obligatoria.'], 400);
            }

            // Validar que se recibió un identificador de usuario
            $waId = $request->input('userIdentifier');
            if (!$waId) {
                return response()->json(['error' => 'El identificador de usuario es obligatorio.'], 400);
            }


            // obtener el bot desde la base de datos
            // Llamar a la función ask para obtener la respuesta del bot
            $botResponse = $this->ask(
                $question,
                $waId,
                $botId,
                $bot->openai_key,
                $bot->openai_org,
                $bot->openai_assistant
            );

            return response()->json(['answer' => $botResponse]);

        } catch (\Exception $e) {
            Log::error('Error en askBotForEmbed: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno en el servidor'], 500);
        }
    }
    // delete thread
    public function deleteThread(Request $request)
    {
        $bot_id = $request->input('id');
        // obtener el bot con el id
        $bot = Bot::find($bot_id);
        // obtener el usuario autenticado
        $user = Auth::user();
        // validar que en thread exista un registro con el wa_id del usuario y el bot_id
        $thread = Thread::where('wa_id', $user->phone)
            ->where('bot_id', $bot_id)
            ->first();
        if (!$thread) {
            return response()->json(['message' => 'No existe un hilo asociado aun']);
        }

        // Cambiar dinámicamente las credenciales de OpenAI
        config(['openai.api_key' => $bot->openai_key]);
        config(['openai.organization' => $bot->openai_org]);

        OpenAI::threads()->delete($thread->thread_id);
        // eliminar de la base de datos
        $thread->delete();
        return response()->json(['message' => 'Hilo eliminado con éxito']);
    }
}
