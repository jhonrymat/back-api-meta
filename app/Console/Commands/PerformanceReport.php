<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Message;

class PerformanceReport extends Command
{
    protected $signature = 'performance:report';
    protected $description = 'Reporte de performance del sistema';

    public function handle()
    {
        $this->info('📊 Reporte de Performance - WhatsApp');
        $this->line('');

        // Mensajes en la última hora
        $lastHour = Message::where('created_at', '>', now()->subHour())->count();
        $this->line("Mensajes (última hora): {$lastHour}");

        // Mensajes fallidos hoy
        $failedToday = Message::where('status', 'failed')
            ->whereDate('created_at', today())
            ->count();
        $this->line("Mensajes fallidos (hoy): {$failedToday}");

        // Promedio de mensajes por minuto
        $avgPerMinute = round($lastHour / 60, 2);
        $this->line("Promedio/min: {$avgPerMinute}");

        // Tabla más grande
        $messageCount = Message::count();
        $this->line("Total mensajes en BD: {$messageCount}");

        // Queries lentas
        $this->line('');
        $this->info('🐌 Queries Lentas (>1s):');

        $slowQueries = DB::select("
            SELECT
                query_time,
                lock_time,
                rows_examined,
                sql_text
            FROM mysql.slow_log
            WHERE query_time > 1
            ORDER BY query_time DESC
            LIMIT 5
        ");

        if (empty($slowQueries)) {
            $this->line('✅ No hay queries lentas');
        } else {
            foreach ($slowQueries as $q) {
                $this->warn("Tiempo: {$q->query_time}s");
                $this->line("  " . substr($q->sql_text, 0, 100));
            }
        }

        return 0;
    }
}
