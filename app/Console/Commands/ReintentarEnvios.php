<?php

namespace App\Console\Commands;

use App\Models\Envio;
use App\Jobs\SendMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

/**
 * Comando para reintentar envíos fallidos o incompletos
 *
 * Uso:
 * php artisan envios:reintentar {envio_id}
 * php artisan envios:reintentar {envio_id} --force (sin preguntar)
 * php artisan envios:reintentar-todos (todos los pendientes)
 */
class ReintentarEnvios extends Command
{
    protected $signature = 'envios:reintentar
                            {envio_id? : ID del envío a reintentar}
                            {--todos : Reintentar todos los envíos pendientes}
                            {--force : No pedir confirmación}
                            {--limit=50 : Límite de destinatarios a reintentar}';

    protected $description = 'Reintentar mensajes fallidos de un envío';

    public function handle()
    {
        if ($this->option('todos')) {
            return $this->reintentarTodos();
        }

        $envioId = $this->argument('envio_id');

        if (!$envioId) {
            $this->error('Debes especificar un ID de envío o usar --todos');
            return 1;
        }

        return $this->reintentarEnvio($envioId);
    }

    /**
     * Reintentar un envío específico
     */
    private function reintentarEnvio($envioId)
    {
        // Buscar el envío
        $envio = Envio::find($envioId);

        if (!$envio) {
            $this->error("❌ Envío #{$envioId} no encontrado");
            return 1;
        }

        $this->info("📋 Analizando envío #{$envio->id}: {$envio->nombrePlantilla}");
        $this->line("   Estado: {$envio->status}");
        $this->line("   Destinatarios: {$envio->numeroDestinatarios}");

        if (!$envio->batch_id) {
            $this->error("❌ Este envío no tiene batch_id asociado");
            return 1;
        }

        // Obtener info del batch
        try {
            $batch = Bus::findBatch($envio->batch_id);

            if (!$batch) {
                $this->warn("⚠️  No se encontró el batch en la BD");
                return $this->reintentarDesdeFailedJobs($envio);
            }

            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Total Jobs', $batch->totalJobs],
                    ['Procesados', $batch->processedJobs()],
                    ['Pendientes', $batch->pendingJobs],
                    ['Fallidos', $batch->failedJobs],
                    ['Progreso', round($batch->progress(), 2) . '%'],
                ]
            );

            // Verificar si hay jobs fallidos
            if ($batch->failedJobs === 0) {
                $this->info("✅ No hay jobs fallidos en este envío");

                if ($batch->pendingJobs > 0) {
                    $this->warn("⚠️  Hay {$batch->pendingJobs} jobs aún pendientes");
                    $this->info("Los jobs pendientes se procesarán automáticamente por los workers");
                }

                return 0;
            }

            // Confirmar reintento
            if (!$this->option('force')) {
                if (!$this->confirm("¿Reintentar {$batch->failedJobs} jobs fallidos?")) {
                    $this->info('Operación cancelada');
                    return 0;
                }
            }

            // Reintentar desde failed_jobs
            return $this->reintentarDesdeFailedJobs($envio);

        } catch (\Exception $e) {
            $this->error("Error al analizar batch: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Reintentar desde la tabla failed_jobs
     */
    private function reintentarDesdeFailedJobs($envio)
    {
        $this->info("\n🔄 Buscando jobs fallidos en la tabla failed_jobs...");

        // Buscar jobs fallidos relacionados con este envío
        $failedJobs = DB::table('failed_jobs')
            ->where('payload', 'like', "%{$envio->batch_id}%")
            ->orWhere('payload', 'like', "%distintivo\":\"{$envio->distintivo}%")
            ->limit($this->option('limit'))
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->warn("⚠️  No se encontraron jobs fallidos para este envío");
            $this->line("\nPosibles razones:");
            $this->line("  • Los jobs ya fueron reintentados");
            $this->line("  • Los jobs fueron eliminados con queue:flush");
            $this->line("  • El distintivo no coincide");
            return 0;
        }

        $this->info("Encontrados {$failedJobs->count()} jobs fallidos");

        // Confirmar
        if (!$this->option('force')) {
            if (!$this->confirm("¿Reintentar estos {$failedJobs->count()} jobs?")) {
                $this->info('Operación cancelada');
                return 0;
            }
        }

        // Reintentar cada job
        $bar = $this->output->createProgressBar($failedJobs->count());
        $bar->start();

        $reintentados = 0;
        $errores = 0;

        foreach ($failedJobs as $failedJob) {
            try {
                // Ejecutar el comando de Laravel para reintentar
                $exitCode = $this->call('queue:retry', ['id' => $failedJob->uuid]);

                if ($exitCode === 0) {
                    $reintentados++;
                } else {
                    $errores++;
                }
            } catch (\Exception $e) {
                $errores++;
                Log::error("Error reintentando job {$failedJob->uuid}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info("✅ Reintentados: {$reintentados}");
        if ($errores > 0) {
            $this->warn("⚠️  Errores: {$errores}");
        }

        // Actualizar estado del envío si es necesario
        if ($reintentados > 0) {
            DB::table('envios')
                ->where('id', $envio->id)
                ->update([
                    'status' => 'Pendiente',
                    'updated_at' => now()
                ]);

            $this->info("\n📊 Estado del envío actualizado a 'Pendiente'");
            $this->line("   Los jobs reintentados se procesarán automáticamente");
        }

        return 0;
    }

    /**
     * Reintentar todos los envíos pendientes/fallidos
     */
    private function reintentarTodos()
    {
        $this->info("🔍 Buscando envíos pendientes o con errores...\n");

        $envios = Envio::whereIn('status', ['Pendiente', 'Completado con errores', 'Fallido'])
            ->where('created_at', '>', now()->subDays(7)) // Solo últimos 7 días
            ->get();

        if ($envios->isEmpty()) {
            $this->info("✅ No hay envíos pendientes");
            return 0;
        }

        $this->table(
            ['ID', 'Plantilla', 'Estado', 'Destinatarios', 'Fecha'],
            $envios->map(fn($e) => [
                $e->id,
                $e->nombrePlantilla,
                $e->status,
                $e->numeroDestinatarios,
                $e->created_at->format('Y-m-d H:i')
            ])
        );

        if (!$this->confirm("¿Reintentar todos estos envíos?")) {
            $this->info('Operación cancelada');
            return 0;
        }

        foreach ($envios as $envio) {
            $this->newLine();
            $this->info("Procesando envío #{$envio->id}...");
            $this->reintentarEnvio($envio->id);
        }

        return 0;
    }
}
