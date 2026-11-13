<?php

namespace App\Jobs;

use App\Domain\Translation\SiteTranslations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Translates the website text that visitors have seen but that has no translation yet, for one
 * language, with the AI key from Admin › AI assistant. Works in batches for about two minutes,
 * then queues itself again while text is still waiting.
 */
class TranslatePendingText implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public string $locale, public int $round = 1) {}

    public function handle(SiteTranslations $translations): void
    {
        if (! $translations->autoReady() || ! array_key_exists($this->locale, config('locales.available'))) {
            return;
        }
        // One worker per language at a time; a second copy simply stops.
        $lock = Cache::lock('tr.run.'.$this->locale, 280);
        if (! $lock->get()) {
            return;
        }
        try {
            $until = microtime(true) + 120;
            $emptyRounds = 0;
            $left = true;
            while ($left && microtime(true) < $until && $emptyRounds < 3) {
                $saved = $translations->translateBatch($this->locale);
                $left = DB::table('site_translations')->where('locale', $this->locale)->where('status', 'pending')->exists();
                $emptyRounds = $saved === 0 ? $emptyRounds + 1 : 0;
            }
        } finally {
            $lock->release();
        }
        if ($left && $emptyRounds < 3 && $this->round < 100) {
            self::dispatch($this->locale, $this->round + 1);
        }
    }
}
