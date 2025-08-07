<?php
namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExportMessagesChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public $startDate,
        public $endDate,
        public $reportId,
        public $idTelefono,
        public $offset,
        public $limit
    ) {
    }

    public function handle()
    {
        $results = DB::select('CALL GetMessagesReportChunked(?, ?, ?, ?, ?)', [
            $this->startDate,
            $this->endDate,
            $this->idTelefono,
            $this->offset,
            $this->limit
        ]);

        if (empty($results)) {
            return;
        }

        $folderPath = storage_path('app/exports');
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true); // ✅ Crea el directorio si no existe
        }

        $filePath = storage_path("app/exports/chunk_{$this->reportId}_{$this->offset}.csv");

        $writer = SimpleExcelWriter::create($filePath);
        foreach ($results as $row) {
            $writer->addRow([
                'ID' => $row->message_id,
                'Nombre' => $row->contacto_nombre,
                'Teléfono' => $row->contacto_telefono,
                'Estado' => $row->estado,
                'Distintivo' => $row->distintivo,
                'Distintivo Nombre' => $row->distintivo_nombre,
                'Fecha' => $row->created_at,
            ]);
        }

        $writer->close();
    }
}
