<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorDatabaseLocks extends Command
{
    protected $signature = 'db:monitor-locks';
    protected $description = 'Monitorea locks de base de datos en tiempo real';

    public function handle()
    {
        $this->info('Monitoreando locks de MySQL (Ctrl+C para detener)...');

        while (true) {
            $this->checkLocks();
            sleep(5); // Cada 5 segundos
        }
    }

    private function checkLocks()
    {
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

        if (!empty($locks)) {
            $this->error('🔒 LOCKS DETECTADOS:');
            foreach ($locks as $lock) {
                $this->warn("Thread {$lock->waiting_thread} esperando a thread {$lock->blocking_thread}");
                $this->line("  Bloqueando: " . substr($lock->blocking_query, 0, 100));
            }
        }
    }
}
