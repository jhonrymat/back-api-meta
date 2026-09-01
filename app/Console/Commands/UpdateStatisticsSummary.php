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
            DB::table('statistics_summary')->truncate();

            $summaries = DB::table('newsletter_statistics as ns')
                ->join('newsletters as n', 'ns.newsletter_id', '=', 'n.id')
                ->selectRaw("
                    ns.newsletter_id,
                    DATE(ns.created_at) as date,
                    COUNT(*) as sent_count,
                    COALESCE(SUM(ns.status = 'Open'), 0) as open_count,
                    COALESCE(SUM(ns.status = 'Bounce'), 0) as error_count,
                    COALESCE(SUM(ns.status = 'Complaint'), 0) as unsubscribe_count,
                    COALESCE(SUM(ns.status = 'Click'), 0) as click_count,
                    COALESCE(SUM(ns.status = 'Delivery'), 0) as delivery_count,
                    COALESCE(SUM(NOT ns.status IN ('Open', 'Bounce', 'Complaint', 'Click', 'Delivery')), 0) as unknown_count,
                    COALESCE(SUM(ns.browser = 'Firefox'), 0) as firefox_count,
                    COALESCE(SUM(ns.browser = 'Chrome'), 0) as chrome_count,
                    COALESCE(SUM(ns.browser = 'Safari'), 0) as safari_count,
                    COALESCE(SUM(ns.browser = 'Edge'), 0) as edge_count,
                    COALESCE(SUM(ns.browser = 'Opera'), 0) as opera_count,
                    COALESCE(SUM(ns.browser = 'Desconocido'), 0) as unknown_browser_count,
                    COALESCE(SUM(ns.operating_system = 'Windows'), 0) as windows_count,
                    COALESCE(SUM(ns.operating_system = 'MacOS'), 0) as macos_count,
                    COALESCE(SUM(ns.operating_system = 'Linux'), 0) as linux_count,
                    COALESCE(SUM(ns.operating_system = 'Android'), 0) as android_count,
                    COALESCE(SUM(ns.operating_system = 'iOS'), 0) as ios_count,
                    COALESCE(SUM(ns.operating_system = 'Desconocido'), 0) as unknown_os_count
                ")
                ->groupBy('ns.newsletter_id', 'date')
                ->get();

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
