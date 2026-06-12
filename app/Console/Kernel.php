<?php

namespace App\Console;

use App\Models\TareaProgramada;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:task --scheduled')
            ->everyFiveMinutes()
            ->when(function () {
                return TareaProgramada::where('fecha_programada', '<=', now())
                    ->where('status', 'pendiente')
                    ->exists();
            })
            ->withoutOverlapping(1500);

        // Ejecutar el comando cada día a las 12:00 AM
        $schedule->command('statistics:update-summary')->dailyAt('00:00');

        // Ejecuta cada hora con la fecha actual
        $schedule->call(function () {
            \Artisan::call('estadisticas:generar', [
                '--fecha' => now()->toDateString(),
            ]);
        })->hourly();

        // Ejecuta diariamente a la 1:00 am con fecha del día anterior
        $schedule->call(function () {
            \Artisan::call('estadisticas:generar', [
                '--fecha' => now()->subDay()->toDateString(),
            ]);
        })->dailyAt('01:00');

        $schedule->command('batches:monitor')->hourly();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
