<?php

namespace App\Jobs;

use App\Models\Numeros;
use App\Models\Reporte;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExportMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $startDate;
    protected $endDate;
    protected $reportId;
    protected $id_telefono;

    public $tries = 3;
    public $timeout = 300;
    public $backoff = 30;

    public function __construct($startDate, $endDate, $reportId, $id_telefono)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->reportId = $reportId;
        $this->id_telefono = $id_telefono;
    }

    public function handle()
    {
        try {
            $results = DB::select('CALL GetMessagesReport(?, ?, ?)', [
                $this->startDate,
                $this->endDate,
                $this->id_telefono
            ]);

            if (!empty($results)) {
                $fileName = 'messages_export_' . now()->format('Y-m-d_His') . '.csv';
                $filePath = storage_path('app/' . $fileName);

                $handle = fopen($filePath, 'w');
                fputcsv($handle, ['ID', 'Nombre', 'Numero', 'Estado', 'Distintivo', 'vacio', 'Fecha']);

                foreach ($results as $row) {
                    fputcsv($handle, (array) $row);
                }
                fclose($handle);

                $report = Reporte::find($this->reportId);
                if ($report) {
                    $report->archivo = $fileName;
                    $report->save();
                }
            } else {
                Log::error("No se encontraron resultados para el reporte {$this->reportId}");
            }

        } catch (Exception $e) {
            Log::error("Error al exportar mensajes: {$e->getMessage()}", ['exception' => $e]);
            throw $e;
        }

        // Notificar al usuario que el reporte está listo
        try {
            $reporte = Reporte::findOrFail($this->reportId);
            $numero = Numeros::where('id_telefono', $reporte->id_telefono)
                ->with('users')
                ->firstOrFail();

            $user = $numero->users()->first();

            if (!$user) {
                Log::warning("No se encontró usuario para el reporte {$this->reportId}");
                return;
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $user->phone,
                'type' => 'template',
                'template' => [
                    'name' => 'reporte_mensual',
                    'language' => ['code' => 'es'],
                    'components' => [
                        [
                            'type' => 'header',
                            'parameters' => [
                                ['type' => 'text', 'text' => $user->name]
                            ]
                        ],
                        [
                            'type' => 'button',
                            'index' => '0',
                            'sub_type' => 'url',
                            'parameters' => [
                                ['type' => 'text', 'text' => (string) $this->reportId]
                            ]
                        ],
                    ]
                ]
            ];

            Http::withToken(env('WHATSAPP_API_TOKEN'))
                ->post(
                    'https://graph.facebook.com/v22.0/' . env('WHATSAPP_API_PHONE_ID') . '/messages',
                    $payload
                )
                ->throw()
                ->json();

            Log::info('Notificación de reporte enviada', ['reporte_id' => $this->reportId]);

        } catch (Exception $e) {
            Log::error('Error al enviar notificación de reporte: ' . $e->getMessage());
        }
    }
}
