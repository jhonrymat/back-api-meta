<?php

use App\Models\Tag;
use App\Models\User;
use App\Events\Webhook;
use App\Models\Contacto;
use App\Models\UserContact;
use App\Models\CustomFieldValue;
use App\Livewire\ContactoComponent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ClocalController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\AplicacionesController;
use Illuminate\Http\Request; // Asegúrate de importar Request

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


Route::get('/public/contact-form/{userId}/{token}', function ($userId, $token) {
    // Validar usuario y token
    $user = User::where('id', $userId)->where('remember_token', $token)->first();

    if (!$user) {
        Log::error("Intento de acceso no autorizado con ID: $userId y Token: $token");
        return response()->json(['error' => 'Acceso no autorizado.'], 403);
    }

    $customFields = $user->customFields;
    $tags = Tag::where('user_id', $user->id)->get(); // 🔹 Aseguramos que obtenemos los tags del usuario

    return view('public.contact-form', compact('customFields', 'tags', 'userId', 'token'));
});



Route::post('/public/store-contact', function (Request $request) {
    try {
        // Obtener datos del formulario
        $userId = $request->input('user_id');
        $token = $request->input('token');

        // Validar usuario con ID y Token
        $user = User::where('id', $userId)
            ->where('remember_token', $token)
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Acceso no autorizado.'], 403);
        }

        // Validar datos del formulario
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string',
            'correo' => 'nullable|email|max:255',
            'notas' => 'nullable|string',
            'etiqueta' => 'sometimes|array',
            'etiqueta.*' => 'integer|exists:tags,id,user_id,' . $user->id,  // Asegura que los tags existen y pertenecen al usuario
        ]);

        // Verificar si el contacto ya existe
        $contacto = Contacto::where('telefono', $data['telefono'])->first();

        if ($contacto) {
            // Si el contacto ya existe, verificar si el usuario actual ya lo tiene asociado
            if (!$user->contactos->contains($contacto->id)) {
                // Asociar el contacto existente con el usuario actual en user_contacts
                $userContact = new UserContact();
                $userContact->user_id = $user->id;
                $userContact->contacto_id = $contacto->id;
                $userContact->save();
            }
        } else {
            // Si no existe, crear un nuevo contacto
            $contacto = new Contacto();
            $contacto->fill($data);
            $contacto->save();

            // Asociar el nuevo contacto con el usuario autenticado en user_contacts
            $userContact = new UserContact();
            $userContact->user_id = $user->id;
            $userContact->contacto_id = $contacto->id;
            $userContact->save();
        }

        // Asociar tags si se proporcionan, tanto para contactos nuevos como existentes
        if (!empty($data['etiqueta'])) {
            $contacto->tags()->syncWithoutDetaching($data['etiqueta']);
        }

        if (isset($data['custom_fields'])) {
            // Guardar los valores de los campos personalizados
            foreach ($request->custom_fields as $fieldId => $value) {
                CustomFieldValue::create([
                    'contacto_id' => $contacto->id,
                    'custom_field_id' => $fieldId,
                    'value' => $value,
                ]);
            }
        }

        // Redirigir con mensaje de éxito
        // Retornar JSON en lugar de redirigir
        return response()->json(['message' => 'Contacto creado correctamente.'], 200);
    } catch (Exception $e) {
        return response()->json(['error' => 'Hubo un problema al crear el contacto.'], 500);
    }
});

