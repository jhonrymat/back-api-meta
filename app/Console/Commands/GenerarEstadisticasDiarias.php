<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerarEstadisticasDiarias extends Command
{
    protected $signature = 'estadisticas:generar
                            {--fecha= : Fecha específica (YYYY-MM-DD), por defecto hoy}';

    protected $description = 'Genera o actualiza las estadísticas diarias desde la tabla messages';

    public function handle()
    {
        $fecha = $this->option('fecha') ?? Carbon::now()->toDateString();

        $this->info("Generando estadísticas para la fecha: $fecha...");

        $registros = DB::table('messages')
            ->selectRaw('DATE(created_at) as fecha, phone_id, status, distintivo, COUNT(*) as total')
            ->where('type', 'template')
            ->whereDate('created_at', $fecha)
            ->groupBy('fecha', 'phone_id', 'status', 'distintivo')
            ->get();

        $this->info("Registros procesados: " . $registros->count());

        foreach ($registros as $registro) {
            DB::table('stats_diarios')->updateOrInsert(
                [
                    'fecha' => $registro->fecha,
                    'phone_id' => $registro->phone_id,
                    'status' => $registro->status,
                    'distintivo' => $registro->distintivo,
                ],
                [
                    'total' => $registro->total,
                    'updated_at' => now(),
                    'created_at' => now(), // solo se asigna la primera vez
                ]
            );
        }

        $this->info('Estadísticas diarias generadas correctamente.');
    }
}
