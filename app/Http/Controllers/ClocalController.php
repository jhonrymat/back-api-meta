<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Clocal;
use App\Models\Numeros;
use App\Models\Distintivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ClocalController extends Controller
{
    public function index()
    {
        $solicitudes = Clocal::all();
        return view('cl/index', [
            'solicitudes' => $solicitudes,
        ]);
    }

    public function send(Request $request, $id)
    {
        $solicitud = Clocal::findOrFail($id);

        // Obtener el usuario logueado
        $user = Auth::user();

        // Verificar si hay un usuario logueado
        if (!$user) {
            // Opcional: manejar el caso en que no haya usuario logueado
            // Por ejemplo, redirigir al login o mostrar un mensaje
            return redirect('login')->with('error', 'Debe estar logueado para ver las aplicaciones.');
        }

        // Obtener las aplicaciones asociadas al usuario logueado
        $aplicaciones = $user->aplicaciones;

        // Obtener solo los números relacionados con las aplicaciones del usuario
        $numeros = Numeros::whereIn('aplicacion_id', $aplicaciones->pluck('id'))->get();


        $tags = $user->tags()->with('contactos')->get();

        $distintivos = Distintivo::all();

        return view('cl/send', [
            'solicitud' => $solicitud,
            'numeros' => $numeros,
            'tags' => $tags,
            'distintivos' => $distintivos,
        ]);
    }

    public function update($id, $status)
    {
        $solicitud = Clocal::findOrFail($id);
        $solicitud->status = $status;
        $solicitud->save();

        return response()->json(['success' => 'Solicitud actualizada con éxito.']);
    }
    public function storeData(Request $request)
    {
        try {
            // Convertir 'publicacion' y 'tag' a string si es necesario, dependiendo de cómo lo manejes en la base de datos
            $publicacion = json_encode($request->publicacion);
            $tag = json_encode($request->tag);

            $cl = new Clocal();
            $cl->empresa = $request->empresa;
            $cl->id_pdf = $request->id_pdf;
            $cl->contrato = $request->contrato;
            $cl->publicacion = $publicacion;
            $cl->codigo_contrato = $request->codigo_contrato;
            $cl->tipo_orden_id = $request->tipo_orden_id;
            $cl->orden_servicio = $request->orden_servicio;
            $cl->desc_general_act = $request->desc_general_act;
            $cl->objeto = $request->objeto;
            $cl->requerimientos = $request->requerimientos;
            $cl->tiempo_ejecucion = $request->tiempo_ejecucion;
            $cl->fecha_inicio = $request->fecha_inicio;
            $cl->fecha_recibo = $request->fecha_recibo;
            $cl->hora_limite = $request->hora_limite;
            $cl->tag = $tag;
            $cl->estado = $request->estado;
            $cl->status = 'pendiente';
            $cl->save();
            return response()->json(['success' => 'Data almacenada con éxito.']);
        } catch (\Exception $e) {
            Log::error('Error al almacenar en la base de datos: ' . $e->getMessage());
            return response()->json([
                'error' => 'Hubo un problema al almacenar la data.',
                'message' => $e->getMessage() // Aquí se envía el mensaje de error específico
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        $app = Clocal::find($request->id);
        if (!$app) {
            return response()->json(['success' => false, 'message' => 'Aplicación no encontrada.'], 404);
        }

        $app->status = $request->status;
        $app->save();

        return response()->json(['success' => true, 'message' => 'Estado actualizado correctamente.']);

    }
}
