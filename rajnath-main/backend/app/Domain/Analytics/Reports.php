<?php

namespace App\Domain\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for Admin › Analytics. Dates are chosen and shown in India time; events are stored
 * in UTC. Every list is a small grouped query over analytics_events or tool_runs.
 */
class Reports
{
    /** India time offset used to group rows by Indian calendar day. */
    private const IST = 'INTERVAL 330 MINUTE';

    public CarbonImmutable $from;

    public CarbonImmutable $to;

    public function __construct(?string $from = null, ?string $to = null, int $days = 30)
    {
        $tz = 'Asia/Kolkata';
        $this->to = $to ? CarbonImmutable::parse($to, $tz)->endOfDay() : CarbonImmutable::now($tz)->endOfDay();
        $this->from = $from ? CarbonImmutable::parse($from, $tz)->startOfDay() : $this->to->subDays(max(1, $days) - 1)->startOfDay();
        if ($this->from->greaterThan($this->to)) {
            [$this->from, $this->to] = [$this->to->startOfDay(), $this->from->endOfDay()];
        }
    }

    public function events(string|array $type, string $channel = 'web'): Builder
    {
        return DB::table('analytics_events')->whereIn('type', (array) $type)->where('channel', $channel)
            ->whereBetween('occurred_at', [$this->from->utc(), $this->to->utc()]);
    }

    /** @return array<string, int|float> */
    public function websiteTotals(): array
    {
        $pv = $this->events('pageview');
        $sessions = (int) (clone $pv)->distinct()->count('session');
        $bounced = (int) DB::query()->fromSub((clone $pv)->select('session')->groupBy('session')->havingRaw('count(*) = 1'), 's')->count();
        $engaged = (int) $this->events('engage')->sum('value');

        return [
            'visitors' => (int) (clone $pv)->distinct()->count('visitor'),
            'new_visitors' => (int) (clone $pv)->where('is_new', true)->count(),
            'sessions' => $sessions,
            'pageviews' => (int) (clone $pv)->count(),
            'signed_in' => (int) (clone $pv)->whereNotNull('user_id')->distinct()->count('user_id'),
            'pages_per_session' => $sessions ? round((clone $pv)->count() / $sessions, 1) : 0,
            'bounce_rate' => $sessions ? (int) round($bounced * 100 / $sessions) : 0,
            'avg_seconds' => $sessions ? (int) round($engaged / $sessions) : 0,
            'clicks' => (int) $this->events('click')->count(),
            'live' => (int) DB::table('analytics_events')->where('channel', 'web')->whereIn('type', ['pageview', 'click', 'engage'])
                ->where('occurred_at', '>=', now()->subMinutes(5))->distinct()->count('visitor'),
        ];
    }

    /**
     * Per-day counts for a chart: [[Y-m-d, value, value2], …] over the whole range.
     *
     * @return list<array{0: string, 1: int, 2: int}>
     */
    public function daily(Builder $q, string $countA = 'count(*)', string $countB = 'count(distinct visitor)', string $col = 'occurred_at'): array
    {
        $rows = $q->selectRaw("DATE(DATE_ADD($col, ".self::IST.")) as d, $countA as a, $countB as b")->groupBy('d')->get()->keyBy('d');
        $out = [];
        for ($d = $this->from; $d->lessThanOrEqualTo($this->to); $d = $d->addDay()) {
            $k = $d->toDateString();
            $out[] = [$k, (int) ($rows[$k]->a ?? 0), (int) ($rows[$k]->b ?? 0)];
        }

        return $out;
    }

    /**
     * Top values of a column with their count and unique visitors.
     *
     * @return list<object{key: ?string, n: int, v: int}>
     */
    public function top(Builder $q, string $column, int $limit = 10): array
    {
        return $q->selectRaw("$column as k, count(*) as n, count(distinct visitor) as v")->groupBy('k')->orderByDesc('n')->limit($limit)->get()->all();
    }

    /** @return list<object> Pages where visits start. */
    public function entryPages(int $limit = 10): array
    {
        $first = $this->events('pageview')->selectRaw('min(id) as id')->groupBy('session');

        return DB::table('analytics_events')->whereIn('id', $first)->selectRaw('path as k, count(*) as n, count(distinct visitor) as v')
            ->groupBy('k')->orderByDesc('n')->limit($limit)->get()->all();
    }

    /** @return list<object> Clicked links and buttons. */
    public function topClicks(int $limit = 15): array
    {
        return $this->events('click')->selectRaw('label, target, count(*) as n, count(distinct visitor) as v')
            ->groupBy('label', 'target')->orderByDesc('n')->limit($limit)->get()->all();
    }

    /** @return list<object{tool: string, runs: int, people: int, pdfs: int, web: int, app: int, signed_in: int}> */
    public function toolTotals(): array
    {
        return DB::table('tool_runs')->whereBetween('created_at', [$this->from->utc(), $this->to->utc()])
            ->selectRaw("tool, count(*) as runs, count(distinct coalesce(visitor, concat('u', user_id), id)) as people, sum(pdf_downloads) as pdfs,
                sum(channel = 'web') as web, sum(channel = 'app') as app, sum(user_id is not null) as signed_in")
            ->groupBy('tool')->orderByDesc('runs')->get()->all();
    }

    public function toolRuns(): Builder
    {
        return DB::table('tool_runs')->whereBetween('created_at', [$this->from->utc(), $this->to->utc()]);
    }

    /** @return array<string, int> */
    public function appTotals(): array
    {
        return [
            'opens' => (int) $this->events('app_open', 'app')->count(),
            'devices' => (int) $this->events(['app_open', 'app_call', 'app_screen'], 'app')->distinct()->count('visitor'),
            'signed_in' => (int) $this->events(['app_open', 'app_call', 'app_screen'], 'app')->whereNotNull('user_id')->distinct()->count('user_id'),
            'calls' => (int) $this->events('app_call', 'app')->count(),
            'tool_runs' => (int) $this->toolRuns()->where('channel', 'app')->count(),
            'today' => (int) DB::table('analytics_events')->where('channel', 'app')->where('type', 'app_open')
                ->where('occurred_at', '>=', CarbonImmutable::now('Asia/Kolkata')->startOfDay()->utc())->distinct()->count('visitor'),
        ];
    }

    /** Readable name for an API route used by the app. */
    public static function feature(?string $route): string
    {
        $r = (string) $route;
        $map = [
            'api/v1/tools' => 'Calculators', 'api/v1/panchang' => 'Panchang', 'api/v1/horoscopes' => 'Rashifal', 'api/v1/rashi' => 'Rashifal (sign page)',
            'api/v1/gochar' => 'Grah gochar', 'api/v1/muhurat' => 'Shubh muhurat', 'api/v1/festivals' => 'Festivals', 'api/v1/calendar' => 'Vrat calendar',
            'api/v1/catalogue' => 'Services', 'api/v1/store' => 'Gem store', 'api/v1/blog' => 'Articles', 'api/v1/gallery' => 'Gallery', 'api/v1/reviews' => 'Reviews',
            'api/v1/assistant' => 'AI assistant', 'api/v1/checkout' => 'Checkout', 'api/v1/bookings' => 'Bookings', 'api/v1/orders' => 'Orders',
            'api/v1/reports' => 'Reports', 'api/v1/palmistry' => 'Palmistry', 'api/v1/puja' => 'Puja', 'api/v1/enquiries' => 'Enquiries',
            'api/v1/birth-profiles' => 'Birth profiles', 'api/v1/wallet' => 'Wallet', 'api/v1/site' => 'About & contact', 'api/v1/faq' => 'FAQ',
            'api/v1/navagraha' => 'Nine planets', 'api/v1/practitioners' => 'Astrologers', 'api/v1/profile' => 'About Acharya Ji', 'api/v1/places' => 'Place search',
        ];
        foreach ($map as $prefix => $name) {
            if (str_starts_with($r, $prefix)) {
                return $name;
            }
        }

        return $r !== '' ? $r : 'Other';
    }
}
