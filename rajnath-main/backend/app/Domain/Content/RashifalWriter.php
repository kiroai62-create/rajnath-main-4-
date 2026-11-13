<?php

namespace App\Domain\Content;

use App\Domain\Assistant\AiProviders;
use App\Domain\Astro\Gochar;
use App\Domain\Astro\Vedic;
use App\Http\Middleware\SetLocale;
use App\Support\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Writes Rashifal automatically with the AI providers set up in Admin › AI assistant.
 * Each reading is grounded on the real planetary positions and transits for the period from
 * our Swiss Ephemeris engine (not copied from other sites). Readings written by staff are never
 * replaced. With "auto-publish" on (default) they go live at once; otherwise they wait as drafts
 * for a publisher in Admin › Rashifal.
 */
class RashifalWriter
{
    /** kind => [period => topics] written automatically. */
    public const PLAN = [
        'rashi' => ['daily' => ['general'], 'weekly' => ['general', 'love', 'career', 'finance', 'health', 'education'],
            'monthly' => ['general', 'love', 'career', 'finance', 'health', 'education'], 'halfyearly' => ['general'], 'yearly' => ['general']],
        'nakshatra' => ['daily' => ['general']],
        'numerology' => ['monthly' => ['general']],
        'tarot' => ['weekly' => ['general'], 'yearly' => ['general']],
        'chinese' => ['yearly' => ['general']],
    ];

    public function __construct(private AiProviders $ai, private Gochar $gochar, private SettingsRepository $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('ai.rashifal.enabled', true) && $this->ai->available();
    }

    /** Start date of the period containing $day (weeks start on Monday). */
    public static function periodStart(string $period, CarbonImmutable $day): CarbonImmutable
    {
        return match ($period) {
            'daily' => $day->startOfDay(), 'weekly' => $day->startOfWeek(CarbonImmutable::MONDAY), 'monthly' => $day->startOfMonth(),
            'halfyearly' => $day->month <= 6 ? $day->startOfYear() : $day->startOfYear()->addMonths(6), 'yearly' => $day->startOfYear(),
        };
    }

    /**
     * Makes sure the current period (and, near its end, the next one) has a reading for every
     * subject. Returns the number of readings written.
     */
    public function ensureCurrent(?CarbonImmutable $today = null, int $maxCalls = 6): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $today ??= CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $written = 0;
        $calls = 0;
        foreach (self::PLAN as $kind => $periods) {
            foreach ($periods as $period => $topics) {
                $start = self::periodStart($period, $today);
                $starts = [$start];
                $end = HoroscopeTypes::endOf($period, $start);
                if ($period !== 'daily' && $today->diffInDays($end) <= 2) {
                    $starts[] = $end->addDay();
                } elseif ($period === 'daily') {
                    $starts[] = $start->addDay();
                }
                foreach ($starts as $s) {
                    foreach ($topics as $topic) {
                        foreach ($this->locales() as $locale) {
                            if ($calls >= $maxCalls) {
                                return $written;
                            }
                            if ($this->missing($kind, $topic, $period, $s, $locale) === []) {
                                continue;
                            }
                            $calls++;
                            try {
                                $written += $this->write($kind, $topic, $period, $s, $locale);
                            } catch (Throwable $e) {
                                report($e);
                            }
                        }
                    }
                }
            }
        }

        return $written;
    }

    /** @return list<string> */
    private function locales(): array
    {
        return array_values(array_intersect(['en', 'hi'], SetLocale::enabled())) ?: ['en'];
    }

    /** Subjects (sign indexes) still without a reading for this period. */
    public function missing(string $kind, string $topic, string $period, CarbonImmutable $start, string $locale): array
    {
        // Readings from the built-in engine writer (no key) count as missing: AI replaces them.
        $have = DB::table('horoscopes')->where(compact('kind', 'topic', 'period', 'locale'))->where('starts_on', $start->toDateString())
            ->where('source', '!=', GocharRashifal::SOURCE)->pluck('sign')->all();

        return array_values(array_diff(array_keys(HoroscopeTypes::subjects($kind, 'en')), $have));
    }

    /** Writes the missing readings of one kind/topic/period with a single AI call. */
    public function write(string $kind, string $topic, string $period, CarbonImmutable $start, string $locale): int
    {
        $missing = $this->missing($kind, $topic, $period, $start, $locale);
        if ($missing === []) {
            return 0;
        }
        $end = HoroscopeTypes::endOf($period, $start);
        $subjects = HoroscopeTypes::subjects($kind, $locale === 'hi' ? 'hi' : 'en');
        $sky = $this->sky($start, $end);
        $words = match ($period) {
            'daily' => '60–90', 'weekly' => '110–150', 'monthly' => '150–200', default => '200–260',
        };
        $lang = $locale === 'hi' ? 'Hindi (Devanagari script)' : 'English';
        $who = match ($kind) {
            'rashi' => 'each Moon sign (Rashi), counting houses from that sign',
            'nakshatra' => 'each of the 27 birth nakshatras (use the transiting Moon\'s nakshatra and Tara Bala counted from the birth nakshatra)',
            'numerology' => 'each Mulank 1–9 (use the universal month number and the ruling planet of each number)',
            'tarot' => 'each Moon sign, as a tarot reading: name one card drawn for the sign and interpret it with the period\'s planetary mood',
            'chinese' => 'each Chinese zodiac animal for the Chinese year starting in this period',
        };
        $list = collect($missing)->map(fn ($i) => $i.' = '.$subjects[$i])->implode('; ');
        $topicText = $topic === 'general' ? 'overall (career, money, relationships, health and a simple remedy)' : $topic;
        $prompt = <<<TXT
        Write the {$period} horoscope ({$topicText}) for {$who}, for {$start->format('d M Y')} to {$end->format('d M Y')}.
        Use ONLY the planetary data below (sidereal, Lahiri). Be specific to each subject, balanced and encouraging, never frightening.
        No guarantees, no medical, legal or financial advice. Include one simple traditional remedy (mantra, daan or colour) and a lucky colour and number.
        Language: {$lang}. Length: {$words} words per subject. Plain text, no headings or markdown.
        Return ONLY a JSON object mapping the subject number to its text, for these subjects: {$list}.

        PLANETARY DATA
        {$sky}
        TXT;
        [$provider] = $result = $this->ai->complete([
            ['role' => 'system', 'content' => 'You are the Rashifal writer for Acharya Dr. Rajnath Jha\'s website (Vedic astrology, Patna). You write clear, warm, accurate horoscopes from the given transit data and return strict JSON.'],
            ['role' => 'user', 'content' => $prompt],
        ], 8000);
        $json = self::extractJson($result[2]);
        $publish = (bool) $this->settings->get('ai.rashifal.autopublish', true);
        $n = 0;
        foreach ($missing as $i) {
            $body = trim(strip_tags((string) ($json[(string) $i] ?? $json[$i] ?? '')));
            if (mb_strlen($body) < 40) {
                continue;
            }
            DB::table('horoscopes')->where(['kind' => $kind, 'topic' => $topic, 'period' => $period, 'sign' => $i, 'locale' => $locale, 'source' => GocharRashifal::SOURCE])
                ->where('starts_on', $start->toDateString())->delete();
            DB::table('horoscopes')->insertOrIgnore([
                'kind' => $kind, 'topic' => $topic, 'period' => $period, 'sign' => $i, 'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString(),
                'locale' => $locale, 'body' => mb_substr($body, 0, 5000), 'status' => $publish ? 'published' : 'draft', 'source' => 'ai', 'author_id' => null,
                'published_at' => $publish ? now() : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $n++;
        }
        if ($n === 0) {
            throw new RuntimeException("Rashifal writer ($provider): no usable text for $kind/$topic/$period.");
        }

        return $n;
    }

    /** Text summary of the sky for the period, from the ephemeris. */
    public function sky(CarbonImmutable $start, CarbonImmutable $end): string
    {
        $tz = new DateTimeZone('Asia/Kolkata');
        $from = new DateTimeImmutable($start->format('Y-m-d').' 06:00', $tz);
        $to = new DateTimeImmutable($end->format('Y-m-d').' 23:59', $tz);
        $lines = [];
        foreach ($this->gochar->now($from) as $p => $x) {
            $lines[] = ucfirst($p).': '.Vedic::SIGNS[$x['sign']].' '.$x['deg'].', nakshatra '.$x['nakshatra'].($x['retro'] && ! in_array($p, ['rahu', 'ketu'], true) ? ', retrograde' : '');
        }
        $events = [];
        foreach (Gochar::PLANETS as $p) {
            if ($p === 'moon' && $start->diffInDays($end) > 10) {
                continue;
            }
            foreach ($this->gochar->events($p, $from, $to, 'Asia/Kolkata') as $e) {
                $events[] = $e['at']->format('d M H:i').' '.ucfirst($p).' '.($e['type'] === 'ingress' ? 'enters '.Vedic::SIGNS[$e['sign']] : 'turns '.$e['type'].' in '.Vedic::SIGNS[$e['sign']]);
            }
        }

        return "Positions at the start:\n".implode("\n", $lines)."\nChanges during the period:\n".($events ? implode("\n", array_slice($events, 0, 40)) : 'none');
    }

    /** First JSON object in a model reply (models sometimes wrap it in a code fence). */
    public static function extractJson(string $text): array
    {
        $text = trim(preg_replace('/^```(?:json)?|```$/m', '', $text));
        $data = json_decode($text, true);
        foreach ([['{', '}'], ['[', ']']] as [$open, $close]) {
            if (is_array($data)) {
                break;
            }
            $a = strpos($text, $open);
            $b = strrpos($text, $close);
            $data = $a !== false && $b !== false ? json_decode(substr($text, $a, $b - $a + 1), true) : null;
        }

        return is_array($data) ? $data : [];
    }
}
