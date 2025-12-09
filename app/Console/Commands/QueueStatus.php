<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class QueueStatus extends Command
{
    protected $signature = 'queue:status';
    protected $description = 'Mostrar estado actual de las colas';

    public function handle()
    {
        $queues = ['whatsapp-queue', 'webhooks-queue', 'default'];

        $this->info('📊 Estado de Colas Redis');
        $this->line('');

        foreach ($queues as $queue) {
            $size = Redis::llen("queues:{$queue}");
            $delayed = Redis::zcard("queues:{$queue}:delayed");
            $reserved = Redis::zcard("queues:{$queue}:reserved");

            $this->line("🔹 {$queue}");
            $this->line("   Pendientes: {$size}");
            $this->line("   Reservados: {$reserved}");
            $this->line("   Retrasados: {$delayed}");
            $this->line('');
        }

        // Failed jobs
        $failed = DB::table('failed_jobs')->count();
        $this->warn("❌ Jobs Fallidos: {$failed}");

        // Workers activos
        $this->line('');
        $this->info('👷 Workers Activos:');
        exec('ps aux | grep "queue:work" | grep -v grep | wc -l', $output);
        $this->line("   Procesos: " . ($output[0] ?? '0'));

        return 0;
    }
}
