<?php

namespace App\Domain\Assistant;

use App\Domain\Notify\StaffAlerts;
use Illuminate\Support\Facades\Cache;

/**
 * Remembers whether the live AI is working and tells the admins when it is not: an email to
 * everyone who can manage providers (at most once every 12 hours per problem) and a banner at the
 * top of the admin panel until a working key is saved. Visitors meanwhile get answers from the
 * website guide (SiteGuide), so the chat never goes dark.
 */
class AiHealth
{
    public const KEY = 'ai.health.v1';

    private const ALERT_EVERY_HOURS = 12;

    /** @return array{state: string, error: ?string, at: string}|null  null = working (or never used) */
    public static function problem(): ?array
    {
        $p = Cache::get(self::KEY);
        if (! app(AiProviders::class)->available()) {
            return ['state' => 'missing', 'error' => null, 'at' => $p['at'] ?? now()->toIso8601String()];
        }

        return is_array($p) && ($p['state'] ?? '') === 'failing' ? $p : null;
    }

    /** The assistant was asked something but no provider is switched on with a key. */
    public static function missing(): void
    {
        self::record('missing', null, 'AI assistant needs an API key',
            "Visitors are using the AI assistant on the website and app, but no AI provider key is set up, so they are getting basic answers from the website only.\n\n"
            ."Please open Admin › AI assistant, paste a key (Groq and Gemini have free keys), tick the provider and \"Assistant switched on\", save, then press Test.\n"
            .'As soon as a key works, the assistant answers with live AI (and web search), and Rashifal is written automatically.');
    }

    /** Every provider failed (wrong, expired or out-of-quota key, or the provider is down). */
    public static function failing(string $error): void
    {
        self::record('failing', mb_substr($error, 0, 400), 'AI key is not working, please check or change it',
            "The AI provider(s) returned an error, so visitors are getting basic answers from the website only.\n\nError: ".mb_substr($error, 0, 400)."\n\n"
            .'Please open Admin › AI assistant, press Test for each provider, and paste a new key if one has expired or run out of quota.');
    }

    public static function ok(): void
    {
        if (Cache::has(self::KEY)) {
            Cache::forget(self::KEY);
            Cache::forget(self::KEY.':alert:missing');
            Cache::forget(self::KEY.':alert:failing');
        }
    }

    private static function record(string $state, ?string $error, string $subject, string $body): void
    {
        Cache::put(self::KEY, ['state' => $state, 'error' => $error, 'at' => now()->toIso8601String()], now()->addDays(7));
        if (Cache::add(self::KEY.':alert:'.$state, 1, now()->addHours(self::ALERT_EVERY_HOURS))) {
            app(StaffAlerts::class)->send('providers.manage', $subject, $body, route('admin.assistant.edit'), '[Action needed] ');
        }
    }
}
