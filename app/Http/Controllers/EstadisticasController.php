<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Envio;
use App\Models\Message;
use App\Models\Reporte;
use App\Jobs\ExportMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EstadisticasController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reportes = $user->reportes()->get();
        $aplicaciones = $user->aplicaciones()->with('numeros')->get();
        return view('estadisticas.index', [
            'reportes' => $reportes,
            'numeros' => $aplicaciones->pluck('numeros')->flatten(),
        ]);
    }

    public function getStatistics(Request $request)
    {
        $datos = $request->all();
        $validatedData = $request->validate([
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'selectPlantilla' => 'required|integer'
        ]);

        $user = Auth::user();
        try {
            $startDate = $validatedData['fechaInicio'];
            $endDate = $validatedData['fechaFin'];
            $selectPlantilla = $validatedData['selectPlantilla'];

            $respote = new Reporte();
            $respote->fechaInicio = $startDate;
            $respote->fechaFin = $endDate;
            $respote->id_telefono = $selectPlantilla;
            $respote->save();

            $user->reportes()->attach($respote->id);

            $reportes = $user->reportes()->get();

            // Obtener el conteo de mensajes por estado en un solo query
            $statusCounts = Message::whereBetween('created_at', [$startDate, $endDate])
                ->where('outgoing', 1)
                ->where('phone_id', $selectPlantilla)
                ->select('status', \DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->keyBy('status'); // Clavear por el estado para un acceso fácil

            $totalMessages = $statusCounts->sum('count'); // Total de mensajes

            // Función para obtener el porcentaje
            $getPercentage = function ($count) use ($totalMessages) {
                return $totalMessages > 0 ? number_format(($count / $totalMessages) * 100, 2) : 0;
            };

            // Usar la función para calcular los porcentajes
            $sent = $getPercentage($statusCounts->get('sent', collect(['count' => 0]))['count']);
            $deliveredPercentage = $getPercentage($statusCounts->get('delivered', collect(['count' => 0]))['count']);
            $read = $getPercentage($statusCounts->get('read', collect(['count' => 0]))['count']);
            $failedPercentage = $getPercentage($statusCounts->get('failed', collect(['count' => 0]))['count']);
            // suma de sent y read
            $sentPercentage = $sent + $read;

            $sentCount = $statusCounts->get('sent', collect(['count' => 0]))['count'];
            $readCount = $statusCounts->get('read', collect(['count' => 0]))['count'];
            // suma de sentCount y readCount
            $sentCount = $sentCount + $readCount;


            return response()->json([
                'totalMessages' => $totalMessages,
                'sentPercentage' => $sentPercentage,
                'deliveredPercentage' => $deliveredPercentage,
                'failedPercentage' => $failedPercentage,
                'deliveredCount' => $statusCounts->get('delivered', collect(['count' => 0]))['count'],
                'failedCount' => $statusCounts->get('failed', collect(['count' => 0]))['count'],
                'sentCount' => $sentCount,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'reportes' => $reportes,
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Error al obtener estadísticas.'], 500);
        }
    }

    public function exportar($id)
    {
        try {

            $report = Reporte::findOrFail($id);
            ExportMessages::dispatch($report->fechaInicio, $report->fechaFin, $id, $report->id_telefono)->onQueue('email-queue');

            return response()->json(['status' => 'Exportación iniciada']);
        } catch (ModelNotFoundException $e) {
            Log::error("Reporte no encontrado: {$e->getMessage()}", ['exception' => $e]);
            return response()->json(['error' => 'Reporte no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error("Error al exportar mensajes: {$e->getMessage()}", ['exception' => $e]);
            return response()->json(['error' => 'Ocurrió un error al exportar el archivo.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




}
