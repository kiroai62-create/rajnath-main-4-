<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports the public Google reviews supplied by the owner (6 Nov 2026). Every written review is
 * published as written, including the critical ones, marked as a Google review and not as a
 * verified purchase. The overall Google rating (all ratings, with or without text) is stored
 * for the "Rated on Google" line. Runs once; later moderation in Admin › Reviews is kept.
 */
class GoogleReviewsSeeder extends Seeder
{
    public const FLAG = 'reviews.google_imported_2026_11_06';

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $data = require database_path('seeders/data/google_reviews.php');
        $collected = CarbonImmutable::parse($data['summary']['as_of'].' 12:00', 'Asia/Kolkata');
        foreach ($data['reviews'] as $r) {
            $at = self::when($r['date'], $collected)->utc();
            DB::table('reviews')->insert([
                'ref' => (string) Str::ulid(), 'rating' => $r['rating'], 'body' => mb_substr($r['body'], 0, 2000), 'display_name' => mb_substr($r['name'], 0, 80),
                'locale' => preg_match('/\p{Devanagari}/u', $r['body']) ? 'hi' : 'en', 'status' => 'approved', 'source' => 'google', 'verified' => false,
                'featured' => $r['featured'], 'permission_note' => 'Public Google review, supplied by the owner on '.$data['summary']['as_of'].' ('.$data['summary']['url'].')',
                'published_at' => $at, 'created_at' => $at, 'updated_at' => now(),
            ]);
        }
        $settings->set('reviews.google', $data['summary']);
        $settings->set(self::FLAG, now()->toDateString());
        Cache::forget('reviews.summary.v1');
    }

    /** Turns Google's "Edited 2 months ago" / "a year ago" into an approximate date. */
    public static function when(string $shown, CarbonImmutable $from): CarbonImmutable
    {
        $s = strtolower(trim(str_replace('edited', '', strtolower($shown))));
        if (! preg_match('/^(a|an|\d+)\s+(day|week|month|year)s?\s+ago$/', $s, $m)) {
            return $from;
        }
        $n = in_array($m[1], ['a', 'an'], true) ? 1 : (int) $m[1];

        return match ($m[2]) {
            'day' => $from->subDays($n), 'week' => $from->subWeeks($n), 'month' => $from->subMonthsNoOverflow($n), 'year' => $from->subYearsNoOverflow($n),
        };
    }
}
