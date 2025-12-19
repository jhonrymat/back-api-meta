<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class CheckBatchStatus extends Command
{
    protected $signature = 'batch:check {batch_id?}';
    protected $description = 'Verifica el estado de un batch o todos los batches recientes';

    public function handle()
    {
        $batchId = $this->argument('batch_id');

        if ($batchId) {
            $this->checkSingleBatch($batchId);
        } else {
            $this->checkRecentBatches();
        }
    }

    private function checkSingleBatch($batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            $this->error("Batch {$batchId} no encontrado");
            return;
        }

        $this->info("📊 Información del Batch: {$batchId}");
        $this->table(
            ['Propiedad', 'Valor'],
            [
                ['Nombre', $batch->name],
                ['Total Jobs', $batch->totalJobs],
                ['Pending Jobs', $batch->pendingJobs],
                ['Processed Jobs', $batch->processedJobs()],
                ['Failed Jobs', $batch->failedJobs],
                ['Progress', $batch->progress() . '%'],
                ['Finished', $batch->finished() ? 'Sí' : 'No'],
                ['Cancelled', $batch->cancelled() ? 'Sí' : 'No'],
                ['Created', $batch->createdAt],
                ['Finished At', $batch->finishedAt ?? 'N/A'],
            ]
        );

        // ⚠️ Detectar anomalías
        if ($batch->totalJobs === 0 && $batch->pendingJobs < 0) {
            $this->error('🚨 ANOMALÍA DETECTADA: Race condition probable');
            $this->warn('  - total_jobs = 0');
            $this->warn('  - pending_jobs < 0');
            $this->warn('  Solución: El batch probablemente funcionó, solo hay error en contadores');
        }
    }

    private function checkRecentBatches()
    {
        $batches = DB::table('job_batches')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->info("📊 Últimos 10 Batches:");

        $data = [];
        foreach ($batches as $batch) {
            $hasAnomaly = $batch->total_jobs === 0 || $batch->pending_jobs < 0;

            $data[] = [
                substr($batch->id, 0, 8),
                $batch->name,
                $batch->total_jobs,
                $batch->pending_jobs,
                $batch->failed_jobs,
                $batch->finished_at ? 'Sí' : 'No',
                $hasAnomaly ? '🚨 ANOMALÍA' : '✅ OK'
            ];
        }

        $this->table(
            ['ID', 'Nombre', 'Total', 'Pending', 'Failed', 'Finished', 'Estado'],
            $data
        );
    }
}
