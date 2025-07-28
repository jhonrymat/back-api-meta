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

        // Valor por defecto si no viene en la URL
        $fecha = $request->input('fecha', now()->toDateString());

        $phoneIds = $user->numeros()->pluck('id_telefono');

        $datos = StatsDiario::where('fecha', $fecha)
            ->whereIn('phone_id', $phoneIds)
            ->orderBy('phone_id')
            ->orderBy('status')
            ->get()
            ->groupBy('phone_id');

        // ✅ Pasar $fecha y $datos a la vista
        return view('conteo.index', compact('fecha', 'datos'));
    }

}
