<?php

use App\Events\Webhook;
use App\Livewire\ContactoComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ClocalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\AplicacionesController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/whatsapp-webhook', [MessageController::class, 'verifyWebhook']);
Route::post('/whatsapp-webhook', [MessageController::class, 'processWebhook']);
Route::get('/send-message', [MessageController::class, 'sendMessages']);

Route::get('/check-session', function () {
    return response()->json(['is_logged_in' => auth()->check()]);
});

// Agrupando rutas y aplicando el middleware 'auth'
Route::middleware(['auth'])->group(function () {
    Route::get('/statistics', [MessageController::class, 'getStatistics']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});
Route::get('contact', ContactoComponent::class)->name('contact.index');

