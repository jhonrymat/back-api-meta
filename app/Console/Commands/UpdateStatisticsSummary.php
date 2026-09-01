<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateStatisticsSummary extends Command
{
    protected $signature = 'statistics:update-summary';
    protected $description = 'Actualizar la tabla de resumen de estadísticas';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info("Iniciando la actualización de estadísticas...");

        try {
            // Limpiar la tabla `statistics_summary` antes de regenerar datos
            DB::table('statistics_summary')->truncate();

            // Obtener estadísticas resumidas desde `newsletter_statistics`
            $summaries = DB::table('newsletter_statistics')
                ->selectRaw("
                    newsletter_id,
                    DATE(created_at) as date,
                    COUNT(*) as sent_count,
                    SUM(status = 'Open') as open_count,
                    SUM(status = 'Bounce') as error_count,
                    SUM(status = 'Complaint') as unsubscribe_count,
                    SUM(status = 'Click') as click_count,
                    SUM(status = 'Delivery') as delivery_count,
                    SUM(NOT status IN ('Open', 'Bounce', 'Complaint', 'Click', 'Delivery')) as unknown_count,
                    SUM(browser = 'Firefox') as firefox_count,
                    SUM(browser = 'Chrome') as chrome_count,
                    SUM(browser = 'Safari') as safari_count,
                    SUM(browser = 'Edge') as edge_count,
                    SUM(browser = 'Opera') as opera_count,
                    SUM(browser = 'Desconocido') as unknown_browser_count,
                    SUM(operating_system = 'Windows') as windows_count,
                    SUM(operating_system = 'MacOS') as macos_count,
                    SUM(operating_system = 'Linux') as linux_count,
                    SUM(operating_system = 'Android') as android_count,
                    SUM(operating_system = 'iOS') as ios_count,
                    SUM(operating_system = 'Desconocido') as unknown_os_count
                ")
                ->groupBy('newsletter_id', 'date')
                ->get();
            // Insertar los datos resumidos en la tabla `statistics_summary`
            $insertData = $summaries->map(function ($summary) {
                return [
                    'newsletter_id' => $summary->newsletter_id,
                    'date' => $summary->date,
                    'sent_count' => $summary->sent_count,
                    'open_count' => $summary->open_count,
                    'error_count' => $summary->error_count,
                    'unsubscribe_count' => $summary->unsubscribe_count,
                    'click_count' => $summary->click_count,
                    'delivery_count' => $summary->delivery_count,
                    'unknown_count' => $summary->unknown_count,
                    'firefox_count' => $summary->firefox_count,
                    'chrome_count' => $summary->chrome_count,
                    'safari_count' => $summary->safari_count,
                    'edge_count' => $summary->edge_count,
                    'opera_count' => $summary->opera_count,
                    'unknown_browser_count' => $summary->unknown_browser_count,
                    'windows_count' => $summary->windows_count,
                    'macos_count' => $summary->macos_count,
                    'linux_count' => $summary->linux_count,
                    'android_count' => $summary->android_count,
                    'ios_count' => $summary->ios_count,
                    'unknown_os_count' => $summary->unknown_os_count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            DB::table('statistics_summary')->insert($insertData);

            $this->info("Estadísticas actualizadas correctamente.");
        } catch (\Exception $e) {
            $this->error("Error al actualizar las estadísticas: " . $e->getMessage());
        }
    }
}
