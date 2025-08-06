<?php

namespace App\Http\Controllers;

use Exception;
use Throwable;
use DataTables;
use App\Models\Tag;
use App\Models\User;
use App\Models\Contacto;
use App\Models\CustomField;
use App\Models\UserContact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Imports\ContactosImport;
use App\Mail\ImportacionFallida;
use App\Models\CustomFieldValue;
use App\Jobs\ImportarContactosJob;
use Illuminate\Support\Facades\DB;
use App\Mail\ImportacionFinalizada;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Bus\Batch;

class ContactoController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('Usuario no autenticado intentando acceder a contactos.');
            return response()->json(['error' => 'Usuario no autenticado.'], 401);
        }
        // Obtener campos personalizados
        $customFields = $user->customFields;
        $tags = $user->tags()->get();
        return view('contactos.index', compact('customFields', 'tags'));
    }

    public function store(Request $request)
    {
        try {

            $user = Auth::user();  // Asegúrate de tener el usuario actual
            // Validar la entrada
            $data = $request->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'sometimes|nullable|string|max:255',
                'correo' => 'sometimes|nullable|email|max:255',
                'telefono' => 'required|string',
                'notas' => 'nullable|string',
                'etiqueta' => 'sometimes|array',
                'etiqueta.*' => [
                    'integer',
                    Rule::exists('tags', 'id')->where('user_id', $user->id),
                ],
            ]);


            // Verificar si existe un contacto con el mismo teléfono
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
                foreach ($data['etiqueta'] as $tagId) {
                    $contacto->tags()->syncWithoutDetaching([
                        $tagId => ['user_id' => $user->id]
                    ]);
                }
            }


            if (isset($request->custom_fields) && is_array($request->custom_fields)) {
                // Guardar los valores de los campos personalizados
                foreach ($request->custom_fields as $fieldId => $value) {
                    if (!is_null($value) && $value !== '') {
                        CustomFieldValue::create([
                            'contacto_id' => $contacto->id,
                            'custom_field_id' => $fieldId,
                            'value' => $value,
                        ]);
                    }
                }

            }



            // Redirigir con mensaje de éxito
            return redirect()
                ->route('contactos.index') // Cambia esto a tu ruta correspondiente
                ->with('success', 'Contacto creado correctamente.');
        } catch (Exception $e) {
            // return redirect()
            //     ->route('contactos.index')
            //     ->with('error', 'Hubo un problema al crear el contacto. Por favor, inténtalo nuevamente.');
            Log::error('Error al crear contacto: ' . $e->getMessage());
            throw $e;

        }
    }


    public function edit($id)
    {
        if (request()->ajax()) {
            try {
                $user = Auth::user(); // Obtener el usuario autenticado
                $customFields = $user->customFields;


                // Cargar el contacto junto con sus tags asociados
                $data = $user->contactos()->where('contactos.id', $id)
                    ->with(['tags', 'customFieldValues.customField'])
                    ->first();

                if (!$data) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contacto no encontrado o no tienes permiso para editarlo.'
                    ], 404);
                }

                // Preparar los valores de los campos personalizados
                $customFieldValues = $data->customFieldValues->pluck('value', 'custom_field_id');


                return response()->json([
                    'result' => $data,
                    'customFields' => $customFields,
                    'customFieldValues' => $customFieldValues
                ]);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }


    public function update(Request $request)
    {
        if (request()->ajax()) {
            try {
                $user = Auth::user(); // Obtener el usuario autenticado

                $contacto = $user->contactos()->where('contactos.id', $request->hidden_id)->with([
                    'tags' => function ($query) use ($user) {
                        $query->where('user_id', $user->id); // Cargar solo tags del usuario
                    },
                    'customFieldValues'
                ])->first();

                if (!$contacto) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contacto no encontrado o no tienes permiso para actualizarlo.'
                    ], 404);
                }

                // Validar la entrada
                $data = $request->validate([
                    'nombre' => 'sometimes|string|max:255',
                    'apellido' => 'sometimes|string|max:255',
                    'correo' => 'sometimes|email|max:255',
                    'telefono' => 'sometimes|string|max:255|unique:contactos,telefono,' . $contacto->id,
                    'notas' => 'nullable|string',
                    'etiqueta' => 'sometimes|array',
                    'etiqueta.*' => 'integer|exists:tags,id,user_id,' . $user->id,
                ]);

                // Actualizar el contacto
                $contacto->update($data);

                // Sincronizar tags específicamente para este usuario
                if (isset($data['etiqueta'])) {
                    // Obtener todos los tag_id actuales del usuario para este contacto
                    $currentTags = $contacto->tags()
                        ->wherePivot('user_id', $user->id)
                        ->pluck('tags.id')
                        ->toArray();

                    $newTags = $data['etiqueta'];

                    $tagsToAdd = array_diff($newTags, $currentTags);
                    $tagsToRemove = array_diff($currentTags, $newTags);

                    // Añadir nuevos
                    foreach ($tagsToAdd as $tagId) {
                        $contacto->tags()->syncWithoutDetaching([
                            $tagId => ['user_id' => $user->id]
                        ]);
                    }

                    // Eliminar antiguos (solo los del usuario actual)
                    if (!empty($tagsToRemove)) {
                        DB::table('contacto_tag')
                            ->where('contacto_id', $contacto->id)
                            ->whereIn('tag_id', $tagsToRemove)
                            ->where('user_id', $user->id)
                            ->delete();
                    }
                }


                // Actualizar los valores de los campos personalizados
                if (isset($data['custom_fields'])) {

                    foreach ($request->custom_fields as $fieldId => $value) {
                        $customFieldValue = $contacto->customFieldValues()->where('custom_field_id', $fieldId)->first();
                        if ($customFieldValue) {
                            $customFieldValue->update(['value' => $value]);
                        } else {
                            CustomFieldValue::create([
                                'contacto_id' => $contacto->id,
                                'custom_field_id' => $fieldId,
                                'value' => $value,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => $contacto
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }





    public function destroy($id)
    {
        if (request()->ajax()) {
            try {
                $user = Auth::user(); // Obtener el usuario autenticado

                // Encontrar el contacto asegurándose de que el usuario tiene permiso para eliminarlo
                $contacto = $user->contactos()->where('contactos.id', $id)->first();

                if (!$contacto) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contacto no encontrado o no tienes permiso para eliminarlo.'
                    ], 404);
                }

                // Eliminar las relaciones de tags antes de eliminar el contacto
                $contacto->tags()->wherePivot('user_id', $user->id)->detach();

                // Eliminar las relaciones de campos personalizados antes de eliminar el contacto
                $contacto->customFieldValues()->delete();

                // Eliminar el contacto
                $contacto->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Contacto eliminado exitosamente.'
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        }
    }

    public function uploadUsers(Request $request)
    {
        // Guardar archivo temporalmente
        $path = $request->file('file')->store('importaciones_temp');

        // Usuario actual
        $userId = Auth::user();
        if (!$userId) {
            return redirect()->route('contactos.index')->withErrors(['error' => 'Usuario no autenticado.']);
        }
        $userId = $userId->id;

        $batch = Bus::batch([
            new ImportarContactosJob($path, $userId),
        ])
            ->then(function (Batch $batch) use ($userId) {
                $omitidasPath = "importaciones/omitidas_{$userId}.json";

                $omitidas = Storage::exists($omitidasPath)
                    ? json_decode(Storage::get($omitidasPath), true)
                    : [];

                // ENVÍA CORREO
                $user = User::find($userId);
                if ($user) {
                    Mail::to($user->email)->send(new ImportacionFinalizada($omitidas)); // Importación con omitidos
                }

                Storage::delete($omitidasPath);

            })
            ->catch(function (Batch $batch, Throwable $e) use ($userId) {
                Log::error("⚠️ Batch fallido para user {$userId}");

                $erroresPath = "importaciones/errores_{$userId}.json";

                $errores = Storage::exists($erroresPath)
                    ? json_decode(Storage::get($erroresPath), true)
                    : [];
                $user = User::find($userId);
                if ($user) {
                    Mail::to($user->email)->send(new ImportacionFallida($errores));
                }
                Storage::delete($erroresPath);
            })
            ->dispatch();

        return redirect()->route('contactos.index')->with('success', 'La importación comenzó en segundo plano. Te notificaremos al finalizar.');
    }

    public function exportar()
    {
        $user = Auth::user(); // Asegúrate de usar Auth para obtener el usuario actual

        // Cargar anticipadamente las etiquetas de los contactos asociados al usuario
        $contactos = $user->contactos()->with('tags')->get();
        $nombreArchivo = 'contactos.csv';

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$nombreArchivo",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columnas = array('ID', 'Nombre', 'Apellido', 'Correo', 'Teléfono', 'Etiquetas'); // Considera incluir otros campos útiles

        $callback = function () use ($contactos, $columnas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columnas);

            foreach ($contactos as $contacto) {
                // Concatenar todas las etiquetas en una cadena separada por comas
                $etiquetas = $contacto->tags->pluck('nombre')->implode(', ');

                // Asegúrate de incluir todos los campos necesarios
                fputcsv($file, [
                    $contacto->id,
                    $contacto->nombre,
                    $contacto->apellido,  // Asumiendo que también quieres incluir el apellido
                    $contacto->correo,    // y el correo electrónico
                    $contacto->telefono,
                    $etiquetas
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function descargarPlantilla()
    {
        $user = Auth::user();
        $customFields = $user->customFields;

        // Definir los encabezados básicos
        $headers = ['nombre', 'apellido', 'correo', 'telefono', 'notas', 'tags'];

        // Agregar los campos personalizados a los encabezados
        foreach ($customFields as $field) {
            $headers[] = $field->name;
        }

        // Crear el contenido del CSV
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        // Enviar el CSV al cliente para su descarga
        return Response::stream($callback, 200, [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=plantilla_contactos.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ]);
    }

    public function getByWaId(Request $request)
    {
        $telefono = $request->wa_id;

        $contacto = Contacto::where('telefono', $telefono)->first();

        if (!$contacto) {
            return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'nombre' => $contacto->nombre,
                'telefono' => $contacto->telefono,
                'notas' => $contacto->notas,
                'tiene_mensajes_nuevos' => $contacto->tiene_mensajes_nuevos,
            ]
        ]);
    }


}
