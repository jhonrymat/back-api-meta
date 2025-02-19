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
        $botId = $request->input('botId');  // Obtener el botId enviado desde el frontend

        if ($botId) {
            // obtener el bot desde la base de datos
            $bot = Bot::find($botId);
            // obterner user autenticado
            $user = Auth::user();


            $question = $request->input('question');
            $waId = $user->phone;  // Obtener el waId del usuario autenticado
            $openai_key = $bot->openai_key;  // Usar el API Key desde el .env
            $openai_org = $bot->openai_org;  // Usar la organización desde el .env
            $openai_assistant = $bot->openai_assistant;  // Usar el assistant ID desde el .env



            // Llamar a la función ask para obtener la respuesta del bot
            $botResponse = $this->ask($question, $waId, $botId, $openai_key, $openai_org, $openai_assistant);



            return response()->json([
                'answer' => $botResponse,  // Devolver la respuesta del bot en formato JSON
            ]);
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

        // Buscar si ya existe un hilo para este usuario y bot específico
        $thread = Thread::where('wa_id', $waId)
            ->where('bot_id', $botId)
            ->first();

        if ($thread) {
            // Si existe un hilo, usar el hilo existente
            $threadRun = $this->continueThread($thread->thread_id, $openai_key, $openai_org, $openai_assistant);
        } else {
            // Si no existe un hilo, crear uno nuevo
            $threadRun = $this->createAndRunThread($openai_key, $openai_org, $openai_assistant);

            // Guardar el nuevo hilo en la base de datos asociado al bot
            Thread::create([
                'wa_id' => $waId,
                'thread_id' => $threadRun->threadId,
                'bot_id' => $botId,  // Relacionar el hilo con el bot correcto
            ]);
        }

        // Cargar la respuesta del hilo
        $this->loadAnswer($threadRun, $openai_key, $openai_org, $openai_assistant, $botId);

        return $this->answer;
    }

    // Método para crear y ejecutar un nuevo hilo
    private function createAndRunThread($openai_key, $openai_org, $openai_assistant)
    {
        $openAI = (new Factory())
            ->withApiKey($openai_key)
            ->withOrganization($openai_org)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        return $openAI->threads()->createAndRun([
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
        $openAI = (new Factory())
            ->withApiKey($openai_key)
            ->withOrganization($openai_org)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        $respuesta = $openAI->threads()->messages()->create(
            $threadId,
            [
                'role' => 'user',
                'content' => $this->question,
            ]
        );

        return $openAI->threads()->runs()->create(
            $threadId,
            [
                'assistant_id' => $openai_assistant,
            ]
        );
    }


    // Método para cargar la respuesta desde el hilo
    private function loadAnswer($threadRun, $openai_key, $openai_org, $openai_assistant, $botId)
    {
        $openAI = (new Factory())
            ->withApiKey($openai_key)
            ->withOrganization($openai_org)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();

        while (in_array($threadRun->status, ['queued', 'in_progress'])) {
            $threadRun = $openAI->threads()->runs()->retrieve(
                $threadRun->threadId,
                $threadRun->id,
            );
        }

        if ($threadRun->status !== 'completed' && $threadRun->status !== 'requires_action') {
            $this->error = 'Request failed, please try again';
            return;
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
                $openAI->threads()->runs()->submitToolOutputs(
                    $threadRun->threadId,
                    $threadRun->id,
                    $tools_output_array
                );
                while (in_array($threadRun->status, ['completed', 'failed', 'requires_action'])) {
                    // Recupera el estado actual de la tarea
                    $threadRun = $openAI->threads()->runs()->retrieve(
                        $threadRun->threadId,
                        $threadRun->id
                    );
                    // Espera 10 segundos antes de la próxima verificación
                    sleep(5);
                }

                // Imprime el estado final después de que se complete el proceso
                \Log::info("Estado final de la tarea: " . $threadRun->status);
            }
        }

        $messageList = $openAI->threads()->messages()->list(
            $threadRun->threadId,
        );

        if ($isResponsePrompt) {
            // Si la respuesta es un prompt, guardar una cadena con el identificador
            $this->answer = 'PROMPT:' . $dataResponse;
        } else {
            // Si no, guardar el mensaje normal
            $this->answer = $messageList->data[0]->content[0]->text->value ?? 'No answer received';
        }
    }

    public function askBotForEmbed(Request $request)
    {
        try {
            $botId = $request->input('botId');
            if ($botId) {
                // obtener el bot desde la base de datos
                $bot = Bot::find($botId);
                $question = $request->input('question');
                $waId = $request->input('userIdentifier');  // Obtener el waId del usuario autenticado
                $openai_key = $bot->openai_key;  // Usar el API Key desde el .env
                $openai_org = $bot->openai_org;  // Usar la organización desde el .env
                $openai_assistant = $bot->openai_assistant;  // Usar el assistant ID desde el .env
                // Lógica para procesar la solicitud y obtener la respuesta del bot
                $botResponse = $this->ask($question, $waId, $botId, $openai_key, $openai_org, $openai_assistant);
            }
            return response()->json(['answer' => $botResponse]);
        } catch (\Exception $e) {
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

        $openAI = (new Factory())
            ->withApiKey($bot->openai_key)
            ->withOrganization($bot->openai_org)
            ->withHttpHeader('OpenAI-Beta', 'assistants=v2')
            ->make();
        $openAI->threads()->delete($thread->thread_id);
        // eliminar de la base de datos
        $thread->delete();
        return response()->json(['message' => 'Hilo eliminado con éxito']);
    }
}
