<?php

use App\Domain\Assistant\Assistant;
use App\Domain\Booking\Slots;
use App\Domain\Content\GocharRashifal;
use App\Domain\Content\RashifalWriter;
use App\Domain\Palmistry\PalmReadings;
use App\Domain\Payments\Reconciler;
use App\Domain\Payments\ServiceDetails;
use App\Domain\Practitioners\Payouts;
use App\Domain\Practitioners\RazorpayX;
use App\Domain\Store\Shiprocket;
use App\Http\Controllers\Admin\HealthController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Payment reconciliation + ledger invariant check (needs `php artisan schedule:work` or cron).
Schedule::call(fn () => app(Reconciler::class)->run())->everyTenMinutes()->name('payments-reconcile')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=720')->daily();
Schedule::call(fn () => app(PalmReadings::class)->purgeOldPhotos() + ServiceDetails::purgeUnpaid())->dailyAt('03:30')->name('palm-photo-retention');
Schedule::call(fn () => app(Slots::class)->expireHolds())->everyMinute()->name('booking-holds')->withoutOverlapping();
Schedule::call(fn () => app(Slots::class)->sendReminders())->everyTenMinutes()->name('booking-reminders')->withoutOverlapping();
Schedule::call(fn () => app(Payouts::class)->release())->dailyAt('02:00')->name('earnings-release');
Schedule::call(fn () => app(Assistant::class)->purge())->dailyAt('03:45')->name('assistant-retention');
// Heartbeat for Admin › Operational health, and provider status polling (webhook fallbacks).
Schedule::call(fn () => Cache::forever(HealthController::HEARTBEAT, now()->toIso8601String()))->everyMinute()->name('scheduler-heartbeat');
Schedule::call(fn () => app(Shiprocket::class)->sync())->hourly()->name('courier-tracking')->withoutOverlapping();
Schedule::call(fn () => app(RazorpayX::class)->sync())->everyFifteenMinutes()->name('razorpayx-payouts')->withoutOverlapping();

// AI Rashifal: keeps the current (and next) day, week, month and year written from the real transits.
Schedule::call(fn () => app(RashifalWriter::class)->ensureCurrent())->hourlyAt(17)->name('rashifal-writer')->withoutOverlapping(30);
// Built-in Rashifal from the ephemeris (no AI key needed): fills whatever the AI writer has not written.
Schedule::call(fn () => app(GocharRashifal::class)->ensureCurrent())->hourlyAt(47)->name('rashifal-engine')->withoutOverlapping(30);

Artisan::command('arj:engine-rashifal', function () {
    $this->info('Built-in Rashifal readings written: '.app(GocharRashifal::class)->ensureCurrent());
})->purpose('Write any missing Rashifal for the current periods from the ephemeris (no AI key needed)');

Artisan::command('arj:write-rashifal {--calls=40 : Most AI calls in this run}', function () {
    $w = app(RashifalWriter::class);
    if (! $w->enabled()) {
        $this->error('Turn on the AI assistant with at least one provider key in Admin › AI assistant first.');

        return 1;
    }
    $this->info('Rashifal readings written: '.$w->ensureCurrent(null, (int) $this->option('calls')));

    return 0;
})->purpose('Write any missing Rashifal for the current periods with AI');
