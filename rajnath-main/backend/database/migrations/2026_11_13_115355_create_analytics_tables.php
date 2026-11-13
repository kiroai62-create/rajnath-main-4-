<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * First-party analytics (no third-party tracker): page views, clicks, engaged time and app
 * opens in analytics_events; every free-calculator run in tool_runs (birth details encrypted);
 * an IP → country table (DB-IP Lite, imported by arj:geoip-import) so no visitor IP is sent
 * anywhere. Raw IP addresses are never stored.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at');                 // UTC
            $table->string('type', 16);                      // pageview | click | engage | tool_run | pdf_download | app_open | app_screen | app_call
            $table->string('channel', 4)->default('web');    // web | app
            $table->string('visitor', 40)->nullable();       // random first-party id (cookie or app install id)
            $table->string('session', 40)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_new')->default(false);       // first page view of a new visitor
            $table->string('path', 300)->nullable();
            $table->string('route', 80)->nullable();
            $table->string('label', 200)->nullable();        // click text, tool slug, app screen
            $table->string('target', 300)->nullable();       // click link
            $table->unsignedInteger('value')->nullable();    // engaged seconds
            $table->string('referrer', 190)->nullable();     // host only
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('device', 8)->nullable();         // mobile | tablet | desktop
            $table->string('os', 16)->nullable();
            $table->string('browser', 16)->nullable();
            $table->string('locale', 8)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->index(['type', 'occurred_at']);
            $table->index(['channel', 'type', 'occurred_at']);
            $table->index('occurred_at');
            $table->index('session');
            $table->index('visitor');
        });

        Schema::create('tool_runs', function (Blueprint $table) {
            $table->id();
            $table->ulid('ref')->unique();
            $table->string('tool', 40);
            $table->string('channel', 4)->default('web');
            $table->foreignId('user_id')->nullable()->index();
            $table->string('visitor', 40)->nullable()->index();
            $table->text('inputs');                          // encrypted JSON (name, birth details)
            $table->string('summary', 300)->nullable();      // short result, e.g. "Lagna Simha · Moon Vrishabha"
            $table->char('country', 2)->nullable();
            $table->string('device', 8)->nullable();
            $table->string('locale', 8)->nullable();
            $table->unsignedInteger('pdf_downloads')->default(0);
            $table->timestamp('last_pdf_at')->nullable();
            $table->timestamps();
            $table->index(['tool', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('geoip_ranges', function (Blueprint $table) {
            $table->id();
            $table->binary('ip_start', 16);                  // 16-byte address; IPv4 stored IPv4-mapped
            $table->binary('ip_end', 16);
            $table->char('country', 2);
            $table->index('ip_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geoip_ranges');
        Schema::dropIfExists('tool_runs');
        Schema::dropIfExists('analytics_events');
    }
};
