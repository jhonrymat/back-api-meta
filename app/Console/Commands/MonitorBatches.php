<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

class MonitorBatches extends Command
{
    protected $signature = 'batches:monitor';
    protected $description = 'Monitorear batches bloqueados o con problemas';

    public function handle()
    {
        // Buscar batches pendientes hace más de 2 horas
        $stuckBatches = DB::table('job_batches')
            ->where('finished_at', null)
            ->where('created_at', '<', now()->subHours(2))
            ->get();

        if ($stuckBatches->isEmpty()) {
            $this->info('✅ No hay batches bloqueados');
            return 0;
        }

        $this->warn("⚠️ {$stuckBatches->count()} batches bloqueados encontrados:");

        foreach ($stuckBatches as $batch) {
            $this->line("Batch ID: {$batch->id}");
            $this->line("  Creado: {$batch->created_at}");
            $this->line("  Total: {$batch->total_jobs}");
            $this->line("  Pendientes: {$batch->pending_jobs}");
            $this->line("  Fallidos: {$batch->failed_jobs}");

            // Opcionalmente, cancelar batches muy antiguos
            if (now()->diffInHours($batch->created_at) > 12) {
                $this->error("  ❌ Cancelando batch antiguo");
                Bus::findBatch($batch->id)?->cancel();
            }
        }

        return 0;
    }
}
