<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Owner decision (7 Nov 2026): home and About stats show 1,35,000+ clients and 100+ awards.
 * Runs once. Updates the saved stats and the "1,00,000 clients" wording in the saved summary
 * and biography (all languages); later edits in Admin › About & awards are kept.
 */
class ProfileStatsSeeder extends Seeder
{
    public const FLAG = 'profile.stats_2026_11_07';

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $settings->set('profile.stat_clients', '1,35,000+', group: 'profile');
        $settings->set('profile.stat_awards', '100+', group: 'profile');
        foreach (DB::table('settings')->where(fn ($q) => $q->where('key', 'like', 'profile.summary%')->orWhere('key', 'like', 'profile.biography%'))->get(['key']) as $row) {
            $text = $settings->get($row->key);
            if (is_string($text) && str_contains($text, '1,00,000')) {
                $settings->set($row->key, str_replace('1,00,000', '1,35,000', $text), group: 'profile');
            }
        }
        $settings->set(self::FLAG, now()->toDateString());
    }
}
