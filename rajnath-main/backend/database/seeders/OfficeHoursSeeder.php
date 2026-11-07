<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Acharya Ji's published office hours as bookable consultation times: Monday–Wednesday and
 * Friday–Sunday, 8 AM–12 PM and 3–7 PM IST, Thursday closed. Runs once and only when no
 * hours exist yet; the owner changes them in Admin › Bookings.
 */
class OfficeHoursSeeder extends Seeder
{
    public const FLAG = 'booking.office_hours_seeded';

    /** Weekdays (0 = Sunday); Thursday (4) is closed. */
    public const DAYS = [0, 1, 2, 3, 5, 6];

    public const BLOCKS = [['08:00', '12:00'], ['15:00', '19:00']];

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG) || DB::table('availability_rules')->where('resource_key', 0)->exists()) {
            return;
        }
        foreach (self::DAYS as $day) {
            foreach (self::BLOCKS as [$from, $to]) {
                DB::table('availability_rules')->insert(['resource_key' => 0, 'weekday' => $day, 'start_local' => $from, 'end_local' => $to, 'slot_minutes' => 30,
                    'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        $settings->set(self::FLAG, now()->toDateString());
    }
}
