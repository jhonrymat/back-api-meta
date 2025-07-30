<?php

use App\Models\Reporte;
use App\Http\Controllers\BotIA;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;
use App\Livewire\ContactoComponent;
// use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\RolesController;

use App\Http\Controllers\ClocalController;
use App\Http\Controllers\ConteoController;
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NumerosController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\AplicacionesController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ProgramadosControllers;
use App\Http\Controllers\EmailTemplateController;

Route::resource(
    'aplicaciones',
    AplicacionesController::class,
)->names('aplicaciones');

//obteniendo numeros telefonicos
Route::get('numbers', [AplicacionesController::class, 'numbers'])->middleware('can:numbers')->name('numbers');

//numeros
Route::resource(
    'numeros',
    NumerosController::class,
)->names('numeros');

//Contactos
Route::get('contactos', [ContactoController::class, 'index'])->middleware('can:contactos.index')->name('contactos.index');
; //mostrar todos los registros
Route::get('contactos/getData', [ContactoController::class, 'getData'])->name('contactos.getData');
Route::post('contactos', [ContactoController::class, 'store'])->name('contactos.store'); //crear un registro
Route::get('contactos/edit/{id}', [ContactoController::class, 'edit']); //obtener datos para editar un registro
Route::post('contactos/update', [ContactoController::class, 'update'])->name('contactos.update'); //actualizare un registro
Route::get('contactos/delete/{id}', [ContactoController::class, 'destroy'])->name('contactos.delete'); //actualizare un registro

// Tags
Route::get('tags', [TagController::class, 'index'])->middleware('can:tags.index')->name('tags.index'); //mostrar todos los registros
Route::post('tags', [TagController::class, 'store'])->name('tags.store'); //crear un registro
Route::put('tags/{id}', [TagController::class, 'update']); //actualizare un registro
Route::delete('tags/{id}', [TagController::class, 'destroy']); //actualizare un registro
Route::get('tags/{id}/contactos', [TagController::class, 'showContacts'])->name('tags.showContacts');

// Users
Route::get('users', [UserController::class, 'index'])->middleware('can:users.index')->name('users.index'); //mostrar todos los registros
Route::post('users', [UserController::class, 'store'])->name('users.store'); //crear un registro
Route::put('users/{id}', [UserController::class, 'update']); //actualizare un registro
Route::delete('users/{id}', [UserController::class, 'destroy']); //actualizare un registro


//roles
Route::get('roles', [RolesController::class, 'index'])->middleware('can:roles.index')->name('roles.index'); //mostrar todos los registros
Route::post('roles', [RolesController::class, 'store'])->name('roles.store'); //crear un registro
Route::put('roles/{id}', [RolesController::class, 'update']); //actualizare un registro
Route::delete('roles/{id}', [RolesController::class, 'destroy']); //actualizare un registro

//permisos
Route::get('permisos', [PermisoController::class, 'index'])->middleware('can:permisos.index')->name('permisos.index'); //mostrar todos los registros
Route::post('permisos', [PermisoController::class, 'store'])->middleware('can:permisos.store')->name('permisos.store'); //crear un registro
Route::put('permisos/{id}', [PermisoController::class, 'update'])->middleware('can:permisos.update')->name('permisos.update'); //actualizare un registro
Route::delete('permisos/{id}', [PermisoController::class, 'destroy'])->middleware('can:permisos.delete')->name('permisos.delete'); //actualizare un registro


// importar contactos
Route::post('upload-contactos', [ContactoController::class, 'uploadUsers'])->middleware('can:importar-contactos')->name('importar-contactos');
// exporta contactos
Route::get('exportar-contactos', [ContactoController::class, 'exportar'])->name('exportar-contactos');


// enviar mensaje plantilla
Route::get('plantillas', [MessageController::class, 'NumbersApps'])->middleware('can:plantillas')->name('plantillas');

Route::get('send-message', [MessageController::class, 'sendMessages']);
Route::get('message-templates', [MessageController::class, 'loadMessageTemplates'])->name('message.templates');
Route::post('send-message-templates', [MessageController::class, 'sendMessageTemplate'])->name('send.message.templates');
Route::post('upload-pdf', [MessageController::class, 'upload'])->middleware('can:upload.pdf')->name('upload.pdf');

//estadisticas
Route::get('estadisticas', [EstadisticasController::class, 'index'])->middleware('can:estadisticas')->name('estadisticas'); //mostrar todos los registros
Route::post('/estadisticas/get-statistics', [EstadisticasController::class, 'getStatistics'])->name('get-statistics');
Route::get('envios-plantillas', [EnvioController::class, 'index'])->middleware('can:envios-plantillas')->name('envios-plantillas'); //mostrar todos los registros
// exporta mensajes
Route::get('exportar-mensajes/{id}', [EstadisticasController::class, 'exportar'])->name('exportar-mensajes');

//programados
Route::get('programados', [ProgramadosControllers::class, 'index'])->middleware('can:programados')->name('programados'); //mostrar todos los registros
Route::get('/descargar-archivo/{id}', [ProgramadosControllers::class, 'descargar'])->name('descargar-archivo');
Route::put('/actualizar-estado/{id}', [ProgramadosControllers::class, 'actualizarEstado'])->name('actualizar-estado');

//contratacion local
Route::get('solicitudes', [ClocalController::class, 'index'])->middleware('can:solicitudes')->name('solicitudes'); //mostrar todos los registroscl
Route::get('solicitudes/send/{id}', [ClocalController::class, 'send'])->name('enviar.solicitud'); //mostrar todos los registroscl
//Cambiar los estados de la tabla de contratacion local
Route::post('update-status-clocal', [ClocalController::class, 'updateStatus'])->name('update.status.clocal');


//descargar informe
Route::get('download/{id}', function ($id) {
    $reporte = Reporte::findOrFail($id);
    $filePath = storage_path('app/' . $reporte->archivo);

    if (file_exists($filePath)) {
        return response()->download($filePath);
    } else {
        return response()->json(['error' => 'Archivo no encontrado.'], 404);
    }
})->name('download');

Route::get('data', function () {
    try {
        $results = DB::select('CALL GetMessagesReport(?, ?)', ['2024-03-29', '2024-04-15']);
        if (!empty($results)) {
            // foreach ($results as $result) {
            //     echo "Nombre: " . $result->contacto_nombre . ", Teléfono: " . $result->contacto_telefono . ", Estado: " . $result->estado . ", enviado: " . $result->distintivo_nombre . ", fecha: " . $result->created_at . "\n";
            // }
            dd($results);
        } else {
            echo "No se encontraron resultados.";
        }
    } catch (\Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
});


Route::resource(
    'messages',
    MessageController::class
);

Route::resource('custom_fields', CustomFieldController::class);

Route::get('messages-index', [MessageController::class, 'chat'])->name('admin.chat'); //mostrar todos los registroscl
Route::get('message-contactos/info', [ContactoController::class, 'getByWaId']);
//envios de errores
Route::post('log-client-error', [ErrorLogController::class, 'store'])->middleware('can:log-client-error')->name('log-client-error');

//verificar si no esta inactiva la sesion del usuario


//actualizando el tocken si este vencio
Route::get('refresh-csrf', function () {
    return response()->json(['csrf_token' => csrf_token()]);
})->middleware('can:refresh-csrf')->name('refresh-csrf');

Route::get('descargar-plantilla', [ContactoController::class, 'descargarPlantilla'])->name('descargar-plantilla');

// enviar correo
// -------------------------------------------
Route::get('/send-email', function () {
    return view('emails.send-email');
})->name('send.email.form');
Route::post('/send-email', [EmailController::class, 'sendEmail'])->name('send.email');

Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
Route::get('/groups/{group}/emails', [GroupController::class, 'showEmails'])->name('groups.showEmails');
Route::post('/groups/{group}/add-recipient', [GroupController::class, 'addRecipient'])->name('groups.addRecipient');
Route::delete('/groups/{group}/remove-recipient/{recipient}', [GroupController::class, 'removeRecipient'])->name('groups.removeRecipient');
Route::get('/groups/{group}/recipients/{recipient}/edit', [GroupController::class, 'editRecipient'])->name('groups.editRecipient');
Route::put('/groups/{group}/recipients/{recipient}', [GroupController::class, 'updateRecipient'])->name('groups.updateRecipient');

// Newsletters
Route::post('newsletters/{newsletter}/send-test', [NewsletterController::class, 'sendTest'])->name('newsletters.sendTest');
Route::post('newsletters/{newsletter}/send', [NewsletterController::class, 'send'])->name('newsletters.send');
Route::resource('newsletters', NewsletterController::class);
Route::get('newsletters/{newsletter}/recipients/count', [NewsletterController::class, 'countRecipients']);
Route::patch('newsletters/{newsletter}/cancel', [NewsletterController::class, 'cancel'])->name('newsletters.cancel');

//conteo de menajes diarios:
Route::get('/conteo/por-dia', [ConteoController::class, 'verPorDia'])->middleware('can:plantillas')->name('conteo.por-dia');

// Genera todas las rutas necesarias para el recurso EmailTemplate
Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
Route::get('email-templates/create', [EmailTemplateController::class, 'create'])->name('email-templates.create');
Route::post('email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
// Route::get('email-templates/{email_template}', [EmailTemplateController::class, 'show'])->name('email-templates.show');
Route::get('/email/preview', [EmailTemplateController::class, 'previewEmail'])->name('email.preview');
Route::get('email-templates/{email_template}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
Route::put('email-templates/{email_template}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
Route::delete('email-templates/{email_template}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');

// -------------------------------------------
// bot
Route::resource(
    'bots',
    BotController::class,
)->names('bots')->middleware('auth');
Route::post('guardar-webhook', [BotController::class, 'guardarWebhook']);
Route::get('verificar-webhook/{botId}', [BotController::class, 'verificarWebhook']);


// ruta de bot, bots.leads
Route::get('leads', [LeadsController::class, 'index'])->name('leads');
Route::post('update-status', [LeadsController::class, 'updateStatus'])->name('update.status');

// crear ruta para crear bot bots.crear.index
Route::post('bots/crear', [BotController::class, 'createBot'])->name('bots.store.asistente');

//ruta json de ejemplo
Route::get('json', function () {

    config(['openai.api_key' => '']);
    config(['openai.organization' => '']);
    // $data = OpenAI::assistants()->list([
    //     'limit' => 10,
    // ]);

    // recuperar un asistente
    $data = OpenAI::assistants()->retrieve('');
    // $data = OpenAI::threads()->runs()->retrieve(
    //     'thread_8wxAlo5zskcpcGHMJrUPF8JB',
    //     'run_4RCYyYzX9m41WQicoJtUQAb8',
    // );

    // $data= OpenAI::threads()->messages()->list('thread_PfcaAuavmdFl1EZUEjldxeuV', [
    //     'limit' => 20,
    // ]);

    return response()->json([
        'success' => 'Bot recuperado con éxito.',
        'data' => $data
    ]);
})->middleware('can:json')->name('json');

// ruta para consultar asistente de openai BotOpenai
Route::get('bots/{botId}/assistant', [BotController::class, 'BotOpenai'])->name('bot-openai');

Route::post('ask-bot', [BotIA::class, 'askBot']);

Route::post('ask-bot-embedded', [BotIA::class, 'askBotForEmbed'])->middleware('cors.custom')->withoutMiddleware('auth');
Route::post('upload-image', [BotIA::class, 'uploadImage'])->middleware('cors.custom')->withoutMiddleware('auth');
// borrar hilo deleteThread
Route::delete('delete-thread', [BotIA::class, 'deleteThread'])->name('deleteThread');
