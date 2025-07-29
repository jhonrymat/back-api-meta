<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\StatsDiario;

class ConteoController extends Controller
{
    public function verPorDia(Request $request)
    {
        $user = Auth::user();

        // Fecha seleccionada o por defecto la actual
        $fecha = $request->input('fecha', now()->toDateString());

        // Obtener los phone_id del usuario autenticado
        $phoneIds = $user->numeros()->pluck('id_telefono');

        // Consultar y agrupar los datos por phone_id
        $datos = StatsDiario::where('fecha', $fecha)
            ->whereIn('phone_id', $phoneIds)
            ->orderBy('phone_id')
            ->orderBy('status')
            ->get()
            ->groupBy('phone_id');

        // Consolidar por estado (ignorando distintivo)
        $agrupados = [];

        foreach ($datos as $phoneId => $registros) {
            $agrupados[$phoneId] = collect($registros)
                ->groupBy('status')
                ->map(function ($items, $status) {
                    return (object)[
                        'status' => $status,
                        'total' => $items->sum('total'),
                    ];
                })->values();
        }

        // Retornar la vista con fecha y datos agrupados
        return view('conteo.index', compact('fecha', 'agrupados'));
    }
}
