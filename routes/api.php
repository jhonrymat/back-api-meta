<?php

use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ClocalController;

use App\Http\Controllers\MessageController;
use App\Http\Controllers\NumerosController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\AplicacionesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Pusher
Route::post('/store-data', [ClocalController::class, 'storeData']);


Route::get('/permitir-imagenes/{botId}', [BotController::class, 'permitirImagenes']);

Route::get('/health/batches', function () {
    $anomalousBatches = DB::table('job_batches')
        ->where(function ($q) {
            $q->where('total_jobs', '=', 0)
                ->orWhere('pending_jobs', '<', 0);
        })
        ->where('created_at', '>', now()->subHours(24))
        ->count();

    $recentBatches = DB::table('job_batches')
        ->where('created_at', '>', now()->subHours(24))
        ->count();

    return response()->json([
        'status' => $anomalousBatches === 0 ? 'healthy' : 'warning',
        'recent_batches' => $recentBatches,
        'anomalous_batches' => $anomalousBatches,
        'anomaly_rate' => $recentBatches > 0
            ? round(($anomalousBatches / $recentBatches) * 100, 2) . '%'
            : '0%'
    ]);
});

// Route::get('/whatsapp-webhook', [MessageController::class, 'verifyWebhook']);
// Route::post('/whatsapp-webhook', [MessageController::class, 'processWebhook']);
// Route::apiResources([
//     'messages' => MessageController::class,
// ]);
// Route::get('/message-templates', [MessageController::class, 'loadMessageTemplates']);
// Route::post('/send-message-templates', [MessageController::class, 'sendMessageTemplate']);

// //estadisticas

// Route::get('/statistics', [MessageController::class, 'getStatistics']);

// //Contactos
// Route::get('/contactos', 'App\Http\Controllers\ContactoController@index');//mostrar todos los registros
// Route::post('/contactos', 'App\Http\Controllers\ContactoController@store');//crear un registro
// Route::put('/contactos/{id}', 'App\Http\Controllers\ContactoController@update');//actualizare un registro
// Route::delete('/contactos/{id}', 'App\Http\Controllers\ContactoController@destroy');//actualizare un registro

// // Tags
// Route::get('/tags', 'App\Http\Controllers\TagController@index');//mostrar todos los registros
// Route::post('/tags', 'App\Http\Controllers\TagController@store');//crear un registro
// Route::put('/tags/{id}', 'App\Http\Controllers\TagController@update');//actualizare un registro
// Route::delete('/tags/{id}', 'App\Http\Controllers\TagController@destroy');//actualizare un registro

// //import
// // Route::get('/import-users', [ContactoController::class, 'importUsers'])->name('import');
// Route::post('/upload-contactos', [ContactoController::class, 'uploadUsers']);

// Route::apiResources([
//     'aplicaciones' => AplicacionesController::class,
// ]);
// //obteniendo numeros telefonicos
// Route::get('/numbers', [AplicacionesController::class, 'numbers']);

// Route::apiResources([
//     'numeros' => NumerosController::class,
// ]);

// //reiniciar servicio de colas
// Route::get('/restart-worker', function () {
//     try {
//         Artisan::call('queue:restart');
//         return response()->json(['success' => true, 'message' => 'Envios reiniciados.']);
//     } catch (\Exception $e) {
//         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
//     }
// });
