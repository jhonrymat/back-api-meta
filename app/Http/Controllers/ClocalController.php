<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Clocal;
use App\Models\Numeros;
use Illuminate\Http\Request;

class ClocalController extends Controller
{
    public function index(){
        $solicitudes = Clocal::all();
        return view('cl/index', [
            'solicitudes' => $solicitudes,
        ]);
    }

    public function send(Request $request, $id){
        $solicitud = Clocal::findOrFail($id);
        $numeros = Numeros::all();
        $tags = Tag::with('contactos')->get();
        return view('cl/send', [
            'solicitud' => $solicitud,
            'numeros'   => $numeros,
            'tags'      => $tags,
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

        if ($cl) {
            return response()->json(['success' => 'Data almacenada con éxito.']);
        } else {
            return response()->json(['error' => 'Hubo un
             problema al almacenar la data.']);
        }
    }
}
