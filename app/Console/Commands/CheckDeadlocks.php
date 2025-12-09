<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeadlocks extends Command
{
    protected $signature = 'db:deadlocks';
    protected $description = 'Verificar deadlocks en la base de datos';

    public function handle()
    {
        // Ver bloqueos actuales
        $locks = DB::select("
            SELECT
                r.trx_id waiting_trx_id,
                r.trx_mysql_thread_id waiting_thread,
                r.trx_query waiting_query,
                b.trx_id blocking_trx_id,
                b.trx_mysql_thread_id blocking_thread,
                b.trx_query blocking_query
            FROM information_schema.innodb_lock_waits w
            INNER JOIN information_schema.innodb_trx b ON b.trx_id = w.blocking_trx_id
            INNER JOIN information_schema.innodb_trx r ON r.trx_id = w.requesting_trx_id
        ");

        if (empty($locks)) {
            $this->info('✅ No hay bloqueos activos');
            return 0;
        }

        $this->warn('⚠️ Bloqueos detectados:');
        foreach ($locks as $lock) {
            $this->line("Esperando: Thread {$lock->waiting_thread}");
            $this->line("  Query: " . substr($lock->waiting_query ?? 'N/A', 0, 100));
            $this->line("Bloqueado por: Thread {$lock->blocking_thread}");
            $this->line("  Query: " . substr($lock->blocking_query ?? 'N/A', 0, 100));
            $this->line('---');
        }

        // Ver último deadlock
        $deadlock = DB::select("SHOW ENGINE INNODB STATUS")[0] ?? null;
        if ($deadlock) {
            $status = $deadlock->Status ?? '';
            if (strpos($status, 'LATEST DETECTED DEADLOCK') !== false) {
                $this->error('❌ Deadlocks recientes detectados en InnoDB');
                $this->line('Ejecuta: SHOW ENGINE INNODB STATUS\\G para más detalles');
            }
        }

        return 0;
    }
}
