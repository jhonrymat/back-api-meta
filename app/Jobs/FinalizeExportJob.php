<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FinalizeExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(public $reportId)
    {
    }

    public function handle()
    {
        $finalPath = storage_path("app/exports/messages_export_{$this->reportId}.csv");
        $writer = SimpleExcelWriter::create($finalPath)
            ->addHeader(['ID', 'Nombre', 'Teléfono', 'Estado', 'Distintivo', 'Distintivo Nombre', 'Fecha']);

        // Unir todos los chunks
        $chunks = glob(storage_path("app/exports/chunk_{$this->reportId}_*.csv"));
        sort($chunks); // Ordena por offset

        foreach ($chunks as $chunkFile) {
            $reader = SimpleExcelReader::create($chunkFile);
            foreach ($reader->getRows() as $row) {
                $writer->addRow($row);
            }
            unlink($chunkFile); // Limpieza
        }

        $writer->close();

        // Actualizar reporte
        $report = \App\Models\Reporte::find($this->reportId);
        $report->archivo = "messages_export_{$this->reportId}.csv";
        $report->save();


        // ✅ Enviar reporte por WhatsApp
        app(\App\Http\Controllers\MessageController::class)->sendReport($this->reportId);
    }
}
