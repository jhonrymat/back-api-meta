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
            // Log de los datos recibidos
            Log::info('=== INICIO storeData ===');
            Log::info('Datos recibidos:', $request->all());

            // Validar que los datos críticos existen
            Log::info('Validando campos requeridos...');

            // Convertir 'publicacion' y 'tag' a string
            $publicacion = json_encode($request->publicacion);
            $tag = json_encode($request->tag);

            Log::info('Publicación convertida:', ['publicacion' => $publicacion]);
            Log::info('Tag convertido:', ['tag' => $tag]);

            // Crear instancia del modelo
            Log::info('Creando instancia de Clocal...');
            $cl = new Clocal();

            // Asignar valores uno por uno con logs
            Log::info('Asignando valores al modelo...');
            $cl->empresa = $request->empresa;
            Log::debug('empresa asignado');

            $cl->id_pdf = $request->id_pdf;
            Log::debug('id_pdf asignado');

            $cl->contrato = $request->contrato;
            Log::debug('contrato asignado');

            $cl->publicacion = $publicacion;
            Log::debug('publicacion asignado');

            $cl->codigo_contrato = $request->codigo_contrato;
            Log::debug('codigo_contrato asignado');

            $cl->tipo_orden_id = $request->tipo_orden_id;
            Log::debug('tipo_orden_id asignado');

            $cl->orden_servicio = $request->orden_servicio;
            Log::debug('orden_servicio asignado');

            $cl->desc_general_act = $request->desc_general_act;
            Log::debug('desc_general_act asignado');

            $cl->objeto = $request->objeto;
            Log::debug('objeto asignado');

            $cl->requerimientos = $request->requerimientos;
            Log::debug('requerimientos asignado');

            $cl->tiempo_ejecucion = $request->tiempo_ejecucion;
            Log::debug('tiempo_ejecucion asignado');

            $cl->fecha_inicio = $request->fecha_inicio;
            Log::debug('fecha_inicio asignado');

            $cl->fecha_recibo = $request->fecha_recibo;
            Log::debug('fecha_recibo asignado');

            $cl->hora_limite = $request->hora_limite;
            Log::debug('hora_limite asignado');

            $cl->tag = $tag;
            Log::debug('tag asignado');

            $cl->estado = $request->estado;
            Log::debug('estado asignado');

            $cl->status = 'pendiente';
            Log::debug('status asignado');

            // Intentar guardar
            Log::info('Intentando guardar en la base de datos...');
            $cl->save();

            Log::info('Registro guardado exitosamente con ID: ' . $cl->id);
            Log::info('=== FIN storeData EXITOSO ===');

            return response()->json(['success' => 'Data almacenada con éxito.']);

        } catch (\Illuminate\Database\QueryException $e) {
            // Error específico de base de datos
            Log::error('=== ERROR DE BASE DE DATOS ===');
            Log::error('Código de error SQL: ' . $e->getCode());
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('SQL: ' . $e->getSql());
            Log::error('Bindings: ', $e->getBindings());

            return response()->json([
                'error' => 'Error de base de datos.',
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], 500);

        } catch (\Exception $e) {
            // Error general
            Log::error('=== ERROR GENERAL ===');
            Log::error('Mensaje: ' . $e->getMessage());
            Log::error('Archivo: ' . $e->getFile());
            Log::error('Línea: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Hubo un problema al almacenar la data.',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
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
