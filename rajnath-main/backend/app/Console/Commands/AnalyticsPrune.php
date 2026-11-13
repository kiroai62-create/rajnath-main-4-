<?php

namespace App\Console\Commands;

use App\Support\Settings\SettingsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Deletes analytics events and calculator history older than the retention set in Admin › Analytics. Runs daily. */
class AnalyticsPrune extends Command
{
    protected $signature = 'arj:analytics-prune';

    protected $description = 'Delete analytics and calculator history older than the retention period.';

    public function handle(SettingsRepository $settings): int
    {
        $days = max(30, (int) $settings->get('analytics.retention_days', 395));
        $before = now()->subDays($days);
        $events = 0;
        do {
            $n = DB::table('analytics_events')->where('occurred_at', '<', $before)->limit(5000)->delete();
            $events += $n;
        } while ($n > 0);
        $runs = DB::table('tool_runs')->where('created_at', '<', $before)->delete();
        $this->info("Deleted $events analytics events and $runs calculator runs older than $days days.");

        return self::SUCCESS;
    }
}
