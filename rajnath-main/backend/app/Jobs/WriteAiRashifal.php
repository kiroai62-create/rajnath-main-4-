<?php

namespace App\Jobs;

use App\Domain\Content\RashifalWriter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Started when a working AI key is saved: writes the Rashifal for the current periods with AI
 * straight away (replacing the built-in engine readings) instead of waiting for the hourly
 * schedule. Each run makes a couple of AI calls and queues the next run until nothing is left.
 */
class WriteAiRashifal implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $round = 1) {}

    public function handle(RashifalWriter $writer): void
    {
        if (! $writer->enabled()) {
            return;
        }
        $written = $writer->ensureCurrent(null, 2);
        if ($written > 0 && $this->round < 40) {
            self::dispatch($this->round + 1);
        }
    }
}
