<?php

namespace App\Domain\Assistant;

use App\Domain\Analytics\ToolHistory;
use App\Domain\Astro\Muhurat;
use App\Domain\Astro\PanchangService;
use App\Domain\Astro\ToolDisplay;
use App\Domain\Astro\Tools;
use App\Domain\Booking\Slots;
use App\Domain\Content\GocharRashifal;
use App\Domain\Content\HoroscopeTypes;
use App\Domain\Legal\LegalDocuments;
use App\Domain\Payments\Checkout;
use App\Domain\Payments\ServiceDetails;
use App\Domain\Places\PlaceSearch;
use App\Domain\Practitioners\Practitioners;
use App\Domain\Showcase\Profile;
use App\Domain\Store\ProductGuide;
use App\Http\Controllers\Api\V1\ContentApiController;
use App\Models\BirthProfile;
use App\Models\Offering;
use App\Models\OfferingVariant;
use App\Models\User;
use App\Support\BookingSteps;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The actions the assistant may take for a visitor: look up services, prices, free times,
 * Panchang, Rashifal, products and articles; search the web; and — for a signed-in customer
 * who has confirmed — hold a consultation time or start an order and hand back the payment
 * link. Payment itself always happens on our checkout, never in the chat.
 */
class AssistantTools
{
    /** Buttons shown under the reply (label, url, and for the app a typed target). */
    public array $actions = [];

    public function __construct(private ?User $user, private string $channel) {}

    /** @return list<array<string, mixed>> */
    public static function definitions(bool $signedIn, bool $webSearch): array
    {
        $fn = fn (string $name, string $desc, array $props = [], array $required = []) => ['type' => 'function', 'function' => [
            'name' => $name, 'description' => $desc,
            'parameters' => ['type' => 'object', 'properties' => (object) $props, 'required' => $required],
        ]];
        $str = fn (string $d) => ['type' => 'string', 'description' => $d];
        $tools = [
            $fn('search_services', 'Find our services (consultations, pujas, reports, Vastu, palmistry, tarot, chadhava) by words or category. Returns slug, title, starting price.', ['query' => $str('Words to search, e.g. "marriage", "shani", "vastu", "court case". Empty for all.'), 'category' => $str('Optional: consultation, puja, report, bundle, vastu, remedy, chadhava, written_question')]),
            $fn('service_details', 'Full details of one service: what it is, vidhi steps, benefits, FAQs, every option with price and option_ref, and how booking works.', ['slug' => $str('Service slug from search_services')], ['slug']),
            $fn('free_times', 'Free consultation times (IST) for a call/in-person option, for the next days.', ['option_ref' => $str('option_ref of a call or in-person option'), 'date' => $str('Optional YYYY-MM-DD to start from')], ['option_ref']),
            $fn('panchang', 'Today\'s (or a date\'s) Panchang: tithi, nakshatra, yoga, karana, sunrise, Rahu Kaal, Choghadiya.', ['date' => $str('YYYY-MM-DD, default today'), 'city' => $str('City name, default Patna')]),
            $fn('rashifal', 'Published Rashifal (horoscope) for a Moon sign.', ['sign' => $str('Sign name in English or Hindi, e.g. Mesha/Aries'), 'period' => $str('daily, weekly, monthly, halfyearly or yearly')], ['sign']),
            $fn('search_store', 'Find gemstones, rudraksha, yantras, malas and puja items in our store with price and stock.', ['query' => $str('Words to search')]),
            $fn('search_articles', 'Find our published articles on astrology, Vastu, festivals and remedies.', ['query' => $str('Words to search')]),
            $fn('product_details', 'Full product page from our store: benefits, who should wear it, how to wear (planet, metal, finger, day, mantra), cautions, care, specs, FAQs, price and stock.', ['slug' => $str('Product slug from search_store')], ['slug']),
            $fn('read_article', 'The full text of one of our articles, to answer from it.', ['slug' => $str('Article slug from search_articles')], ['slug']),
            $fn('about_acharya', 'About Acharya Dr. Rajnath Jha: biography, qualifications, stats (years, clients, awards), awards and media, languages.'),
            $fn('policy', 'Our published policy text: privacy, terms, refunds or shipping.', ['name' => $str('privacy, terms, refunds or shipping')], ['name']),
            $fn('planet_transits', 'Where the nine planets (grahas) are today: sign, degree, nakshatra, retrograde.'),
            $fn('festivals', 'Hindu festival and vrat dates for a year (with dates and short notes).', ['year' => $str('Year, default this year'), 'month' => $str('Optional month number 1-12 to filter')]),
            $fn('muhurat', 'Shubh muhurat dates for an event in a month (vivah, griha pravesh, mundan, vehicle, property, business and more).', ['event' => $str('Event key or name, e.g. vivah, griha-pravesh, vehicle'), 'month' => $str('YYYY-MM, default this month')], ['event']),
            $fn('run_calculator', 'Run one of our free calculators for the visitor and get the result (kundli, kundli-matching, moon-sign, nakshatra, lagna-navamsa, sade-sati, pitra-dosh, rahu-ketu, numerology, name-number, destiny-number, lo-shu-grid, personal-year, lucky-colour, lucky-dates, vehicle-number, rudraksha, baby-name, love-calculator and more). Ask for the needed details first.', [
                'slug' => $str('Calculator slug'), 'name' => $str('Name, if needed'), 'birth_date' => $str('YYYY-MM-DD'), 'birth_time' => $str('HH:MM 24h'),
                'birth_place' => $str('Birth city, e.g. Patna'), 'partner' => $str('Partner name (love-calculator)'), 'month' => $str('YYYY-MM (lucky dates)'), 'plate' => $str('Vehicle number'),
                'b_name' => $str('Second person name (matching)'), 'b_birth_date' => $str('Second person YYYY-MM-DD'), 'b_birth_time' => $str('Second person HH:MM'), 'b_birth_place' => $str('Second person city'),
            ], ['slug']),
            $fn('site_page', 'Links to site pages: about, awards, gallery, contact, reviews, faq, privacy, terms, refund-policy, shipping-policy, calculators, panchang, festivals, muhurat.', ['page' => $str('Page name')], ['page']),
        ];
        if ($webSearch) {
            $tools[] = $fn('web_search', 'Search the internet for current facts not on our site (festival dates in other regions, news, general knowledge). Do not use for our prices or services.', ['query' => $str('Search query')], ['query']);
        }
        if ($signedIn) {
            $tools[] = $fn('my_birth_profiles', 'The signed-in customer\'s saved birth profiles (ref, name, date of birth).');
            $tools[] = $fn('my_activity', 'The signed-in customer\'s recent orders, bookings, pujas, reports and palm readings with status.');
            $tools[] = $fn('book_consultation', 'Hold a consultation time for the signed-in customer and return the payment link. Call ONLY after the customer clearly confirmed the service option, date and time.', [
                'option_ref' => $str('option_ref of the call/in-person option'), 'start' => $str('Start time exactly as returned by free_times (UTC, e.g. 2026-11-08T05:30Z)'),
                'birth_profile_ref' => $str('Optional birth profile ref'), 'topic' => $str('Optional: what the customer wants to discuss'),
            ], ['option_ref', 'start']);
            $tools[] = $fn('start_order', 'Start an order for a non-call option (puja, report, palm photo report, written answer, site visit) for the signed-in customer and return the payment link. Call ONLY after the customer confirmed the option and price.', ['option_ref' => $str('option_ref')], ['option_ref']);
        }

        return $tools;
    }

    public function run(string $name, array $a): array
    {
        return match ($name) {
            'search_services' => $this->searchServices((string) ($a['query'] ?? ''), (string) ($a['category'] ?? '')),
            'service_details' => $this->serviceDetails((string) ($a['slug'] ?? '')),
            'free_times' => $this->freeTimes((string) ($a['option_ref'] ?? ''), $a['date'] ?? null),
            'panchang' => $this->panchang($a['date'] ?? null, $a['city'] ?? null),
            'rashifal' => $this->rashifal((string) ($a['sign'] ?? ''), (string) ($a['period'] ?? 'daily')),
            'search_store' => $this->searchStore((string) ($a['query'] ?? '')),
            'search_articles' => $this->searchArticles((string) ($a['query'] ?? '')),
            'site_page' => $this->sitePage((string) ($a['page'] ?? '')),
            'product_details' => $this->productDetails((string) ($a['slug'] ?? '')),
            'read_article' => $this->readArticle((string) ($a['slug'] ?? '')),
            'about_acharya' => $this->aboutAcharya(),
            'policy' => $this->policy((string) ($a['name'] ?? '')),
            'planet_transits' => $this->apiData('gochar', [], 'gochar'),
            'festivals' => $this->festivals($a['year'] ?? null, $a['month'] ?? null),
            'muhurat' => $this->muhurat((string) ($a['event'] ?? ''), $a['month'] ?? null),
            'run_calculator' => $this->calculator($a),
            'web_search' => app(AiProviders::class)->webSearch((string) ($a['query'] ?? '')) ?? ['error' => 'Web search is not available.'],
            'my_birth_profiles' => $this->needUser() ?? $this->birthProfiles(),
            'my_activity' => $this->needUser() ?? $this->activity(),
            'book_consultation' => $this->needUser() ?? $this->book((string) ($a['option_ref'] ?? ''), (string) ($a['start'] ?? ''), $a['birth_profile_ref'] ?? null, $a['topic'] ?? null),
            'start_order' => $this->needUser() ?? $this->startOrder((string) ($a['option_ref'] ?? '')),
            default => ['error' => 'Unknown tool.'],
        };
    }

    private function needUser(): ?array
    {
        return $this->user ? null : ['error' => 'The visitor is not signed in. Ask them to sign in (top right, with a one-time code) and then continue.'];
    }

    private function like(string $q): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], mb_substr(trim($q), 0, 80)).'%';
    }

    private function searchServices(string $q, string $category): array
    {
        $words = array_filter(preg_split('/\s+/', mb_strtolower(trim($q))) ?: [], fn ($w) => mb_strlen($w) > 2);
        $list = Offering::public()->with(['translations', 'variants', 'family'])
            ->when($category !== '', fn ($w) => $w->whereHas('family', fn ($f) => $f->where('code', $category)))
            ->orderBy('sort')->get()
            ->map(function ($o) use ($words) {
                $t = $o->t('en');
                $hay = mb_strtolower($o->slug.' '.$t?->title.' '.$t?->summary.' '.$t?->body);
                $score = $words ? collect($words)->sum(fn ($w) => substr_count($hay, $w) + (str_contains(mb_strtolower((string) $t?->title), $w) ? 5 : 0)) : 1;

                return ['score' => $score, 'slug' => $o->slug, 'title' => $t?->title, 'category' => $o->family?->code, 'summary' => Str::limit((string) $t?->summary, 140),
                    'from_price' => Money::format($o->fromPriceMinor())];
            })->filter(fn ($r) => $r['score'] > 0)->sortByDesc('score')->take(8)->map(fn ($r) => collect($r)->except('score')->all())->values()->all();

        return ['services' => $list];
    }

    private function serviceDetails(string $slug): array
    {
        $o = Offering::public()->with(['translations', 'variants', 'family'])->where('slug', Offering::MOVED[$slug] ?? $slug)->first();
        if (! $o) {
            return ['error' => 'No such service. Use search_services.'];
        }
        $t = $o->t('en');
        $this->actions[] = ['label' => 'Open '.$t->title, 'url' => route('services.show', $o->slug), 'type' => 'service', 'slug' => $o->slug];

        return [
            'slug' => $o->slug, 'title' => $t->title, 'summary' => $t->summary, 'about' => Str::limit((string) $t->body, 1500),
            'vidhi' => $t->process ?? [], 'benefits' => $t->benefits ?? [], 'includes' => $t->includes ?? [], 'faqs' => array_slice($t->faqList(), 0, 4),
            'options' => $o->variants->where('active', true)->values()->map(fn ($v) => ['option_ref' => $v->ref, 'name' => $v->label(), 'mode' => $v->mode,
                'minutes' => $v->duration_min, 'price' => Money::format($v->price_minor), 'booked_by_time_slot' => Slots::bookable($v)])->all(),
            'how_booking_works' => BookingSteps::for($o), 'page' => route('services.show', $o->slug),
        ];
    }

    private function variant(string $ref): ?OfferingVariant
    {
        $v = OfferingVariant::where('ref', $ref)->where('active', true)->first();

        return $v && $v->offering?->status === 'active' ? $v : null;
    }

    private function freeTimes(string $ref, ?string $from): array
    {
        $v = $this->variant($ref);
        if (! $v || ! Slots::bookable($v)) {
            return ['error' => 'This option is not booked by time. Use start_order instead.'];
        }
        if (Practitioners::TIER_OFFERINGS[$v->offering->slug] ?? null) {
            $this->actions[] = ['label' => 'Choose an astrologer and time', 'url' => route('account.book', $v->ref), 'type' => 'service', 'slug' => $v->offering->slug];

            return ['note' => 'For team astrologers the customer picks the astrologer on the booking page.', 'link' => route('account.book', $v->ref)];
        }
        $days = app(Slots::class)->available((int) $v->duration_min, 21, 0);
        $fromDate = $from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : now(Slots::TZ)->toDateString();
        $out = [];
        foreach ($days as $date => $slots) {
            if ($date < $fromDate) {
                continue;
            }
            $out[] = ['date' => $date, 'day' => CarbonImmutable::parse($date)->format('l, d M'), 'times' => collect($slots)->take(10)->map(fn ($s) => ['start' => $s['start'],
                'ist' => CarbonImmutable::parse($s['start'])->setTimezone(Slots::TZ)->format('g:i A')])->all()];
            if (count($out) >= 4) {
                break;
            }
        }

        return $out ? ['days' => $out, 'timezone' => 'IST'] : ['note' => 'No free times in the next three weeks. Offer the office phone or the request form.'];
    }

    private function panchang(?string $date, ?string $city): array
    {
        $date = $date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : now('Asia/Kolkata')->toDateString();
        $place = app(PlaceSearch::class)->search($city ?: 'Patna', 1)[0] ?? null;
        if (! $place) {
            return ['error' => 'City not found.'];
        }
        $p = DB::table('places')->where('id', $place['id'])->first();
        $d = app(PanchangService::class)->day($date, (float) $p->lat, (float) $p->lng, $p->tz_name);
        $this->actions[] = ['label' => 'Full Panchang', 'url' => route('panchang', ['date' => $date, 'place_id' => $p->id]), 'type' => 'page', 'page' => 'panchang'];

        return ['city' => $place['label'], 'date' => $date] + collect($d)->except(['choghadiya', 'hora'])->all() + ['choghadiya_day' => array_slice($d['choghadiya']['day'] ?? [], 0, 8)];
    }

    private function rashifal(string $sign, string $period): array
    {
        $names = HoroscopeTypes::subjects('rashi', 'en');
        $hi = HoroscopeTypes::subjects('rashi', 'hi');
        $s = mb_strtolower(trim($sign));
        $west = ['aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces'];
        $idx = collect(range(0, 11))->first(fn ($i) => str_contains(mb_strtolower($names[$i]), $s) || str_contains($hi[$i], trim($sign)) || $west[$i] === $s);
        if ($idx === null) {
            return ['error' => 'Unknown sign.'];
        }
        $period = array_key_exists($period, HoroscopeTypes::PERIODS) ? $period : 'daily';
        $today = now('Asia/Kolkata')->toDateString();
        GocharRashifal::fillToday('rashi', $period);
        $rows = DB::table('horoscopes')->where('status', 'published')->where('kind', 'rashi')->where('sign', $idx)->where('period', $period)
            ->where('starts_on', '<=', $today)->where('ends_on', '>=', $today)->orderBy('topic')->get(['topic', 'body', 'starts_on', 'ends_on', 'locale']);
        $this->actions[] = ['label' => 'Rashifal', 'url' => route('rashifal', ['period' => $period]), 'type' => 'page', 'page' => 'rashifal'];

        return $rows->isEmpty() ? ['note' => 'Not published yet for this period.'] : ['sign' => $names[$idx], 'period' => $period, 'readings' => $rows->map(fn ($r) => (array) $r)->all()];
    }

    private function searchStore(string $q): array
    {
        $rows = DB::table('products')->where('active', true)
            ->when(trim($q) !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', $this->like($q))->orWhere('summary', 'like', $this->like($q))->orWhere('category', 'like', $this->like($q))))
            ->orderBy('sort')->limit(8)->get(['slug', 'name', 'category', 'weight', 'price_minor', 'stock']);
        if ($rows->isNotEmpty()) {
            $this->actions[] = ['label' => 'Open the store', 'url' => route('store.index'), 'type' => 'page', 'page' => 'store'];
        }

        return ['products' => $rows->map(fn ($p) => ['name' => $p->name, 'category' => $p->category, 'weight' => $p->weight, 'price' => Money::format((int) $p->price_minor),
            'in_stock' => $p->stock > 0, 'url' => route('store.show', $p->slug)])->all()];
    }

    private function searchArticles(string $q): array
    {
        $rows = DB::table('posts')->where('status', 'published')->where('published_at', '<=', now())
            ->when(trim($q) !== '', fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', $this->like($q))->orWhere('excerpt', 'like', $this->like($q))->orWhere('body', 'like', $this->like($q))))
            ->orderByDesc('published_at')->limit(5)->get(['slug', 'title', 'excerpt']);

        return ['articles' => $rows->map(fn ($p) => ['slug' => $p->slug, 'title' => $p->title, 'summary' => $p->excerpt, 'url' => route('blog.show', $p->slug)])->all()];
    }

    private function productDetails(string $slug): array
    {
        $p = DB::table('products')->where('slug', $slug)->where('active', true)->first();
        if (! $p) {
            return ['error' => 'Product not found. Use search_store first.'];
        }
        $this->actions[] = ['label' => 'View '.$p->name, 'url' => route('store.show', $p->slug), 'type' => 'product', 'slug' => $p->slug];

        return ['name' => $p->name, 'summary' => $p->summary, 'description' => Str::limit((string) $p->description, 1500), 'weight' => $p->weight, 'origin' => $p->origin,
            'certificate' => $p->certification, 'price' => Money::format((int) $p->price_minor), 'in_stock' => $p->stock > 0, 'url' => route('store.show', $p->slug)]
            + collect(ProductGuide::for($p))->except('credit')->all();
    }

    private function readArticle(string $slug): array
    {
        $p = DB::table('posts')->where('slug', $slug)->where('status', 'published')->where('published_at', '<=', now())->first();
        if (! $p) {
            return ['error' => 'Article not found. Use search_articles first.'];
        }
        $this->actions[] = ['label' => 'Read the article', 'url' => route('blog.show', $p->slug), 'type' => 'article', 'slug' => $p->slug];

        return ['title' => $p->title, 'text' => Str::limit(strip_tags((string) $p->body), 6000), 'url' => route('blog.show', $p->slug)];
    }

    private function aboutAcharya(): array
    {
        $this->actions[] = ['label' => 'About Acharya Ji', 'url' => route('about'), 'type' => 'page', 'page' => 'about'];
        $d = app(Profile::class)->toApi();

        return collect($d)->except(['photos', 'web'])->map(fn ($v) => is_string($v) ? Str::limit($v, 2500) : $v)->all();
    }

    private function policy(string $name): array
    {
        $kind = collect(array_keys(LegalDocuments::KINDS))->first(fn ($k) => str_contains($k, mb_strtolower(trim($name))) || str_contains(mb_strtolower($name), rtrim($k, 's')));
        $doc = $kind ? app(LegalDocuments::class)->current($kind) : null;
        if (! $doc) {
            return ['error' => 'Policy not found.', 'available' => array_keys(LegalDocuments::KINDS)];
        }

        return ['title' => (string) ($doc->title ?? LegalDocuments::KINDS[$kind][0] ?? $kind), 'text' => Str::limit(strip_tags((string) ($doc->body ?? '')), 6000)];
    }

    private function festivals(mixed $year, mixed $month): array
    {
        $r = $this->apiData('festivals', ['year' => is_numeric($year) ? (int) $year : now('Asia/Kolkata')->year], 'festivals');
        if (isset($r['data']) && is_numeric($month)) {
            $m = str_pad((string) (int) $month, 2, '0', STR_PAD_LEFT);
            $r['data'] = array_values(array_filter($r['data'], fn ($f) => substr((string) ($f['date'] ?? ''), 5, 2) === $m));
        }
        if (isset($r['data'])) {
            $r['data'] = array_slice($r['data'], 0, 60);
        }

        return $r;
    }

    private function muhurat(string $event, mixed $month): array
    {
        $key = collect(array_keys(Muhurat::EVENTS))->first(fn ($k) => $k === $event || str_contains($k, Str::slug($event)) || str_contains(mb_strtolower(Muhurat::EVENTS[$k]['name']), mb_strtolower($event)));
        if (! $key) {
            return ['error' => 'Unknown event.', 'events' => array_keys(Muhurat::EVENTS)];
        }
        $q = is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) ? ['month' => $month] : [];

        return $this->apiData('muhurat', $q, 'muhurat', ['event' => $key]);
    }

    private function calculator(array $a): array
    {
        $slug = (string) ($a['slug'] ?? '');
        if (! isset(Tools::TOOLS[$slug])) {
            return ['error' => 'Unknown calculator.', 'calculators' => array_keys(Tools::TOOLS)];
        }
        $in = array_filter(['name' => $a['name'] ?? null, 'birth_date' => $a['birth_date'] ?? null, 'birth_time' => $a['birth_time'] ?? null,
            'partner' => $a['partner'] ?? null, 'month' => $a['month'] ?? null, 'plate' => $a['plate'] ?? null], fn ($v) => $v !== null && $v !== '');
        $place = fn ($city) => $city ? (app(PlaceSearch::class)->search((string) $city, 1)[0]['id'] ?? null) : null;
        if (Tools::TOOLS[$slug][1] === 'couple') {
            $in = ['a_name' => $a['name'] ?? null, 'a_birth_date' => $a['birth_date'] ?? null, 'a_birth_time' => $a['birth_time'] ?? null, 'a_place_id' => $place($a['birth_place'] ?? null),
                'b_name' => $a['b_name'] ?? null, 'b_birth_date' => $a['b_birth_date'] ?? null, 'b_birth_time' => $a['b_birth_time'] ?? null, 'b_place_id' => $place($a['b_birth_place'] ?? null)];
        } elseif (Tools::TOOLS[$slug][1] === 'birth') {
            $in['place_id'] = $place($a['birth_place'] ?? null);
        }
        $v = validator($in, Tools::rules(Tools::TOOLS[$slug][1]));
        if ($v->fails()) {
            return ['error' => 'Some details are missing or wrong. Ask the visitor for them.', 'problems' => $v->errors()->all()];
        }
        try {
            $r = app(Tools::class)->compute($slug, $v->validated());
        } catch (\RuntimeException $e) {
            report($e);

            return ['error' => 'The calculation service is busy. Ask them to try the calculator page.'];
        }
        $this->actions[] = ['label' => 'Open '.Tools::TOOLS[$slug][0], 'url' => route('tools.show', $slug), 'type' => 'page', 'page' => 'tools'];
        app(ToolHistory::class)->record($slug, $v->validated() + ['via' => 'AI assistant'], $r, request(), $this->channel === 'app' ? 'app' : 'web', ToolHistory::visitorFor(request()));
        $display = ToolDisplay::for($r);

        return ['calculator' => Tools::TOOLS[$slug][0], 'result' => Str::limit(json_encode($display, JSON_UNESCAPED_UNICODE), 7000)];
    }

    /**
     * Reads one of our own public API answers (the same data the app shows) by calling the API
     * controller directly, so the assistant always sees what the website shows.
     *
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $params
     */
    private function apiData(string $method, array $query, string $page, array $params = []): array
    {
        try {
            $r = app()->call([app(ContentApiController::class), $method], ['request' => Request::create('/', 'GET', $query)] + $params);
        } catch (ValidationException) {
            return ['error' => 'Those details are not valid.'];
        }
        $data = $r instanceof JsonResponse ? $r->getData(true) : null;
        if (! $r instanceof JsonResponse || ! $r->isSuccessful() || ! is_array($data)) {
            return ['error' => 'Not available right now.'];
        }
        $route = $page === 'muhurat' ? 'muhurat.index' : $page;
        if (Route::has($route)) {
            $this->actions[] = ['label' => 'Open '.ucfirst($page), 'url' => route($route), 'type' => 'page', 'page' => $page];
        }

        return $data;
    }

    private function sitePage(string $page): array
    {
        $map = ['about' => 'about', 'awards' => 'awards', 'gallery' => 'gallery', 'contact' => 'contact', 'reviews' => 'reviews', 'faq' => 'faq', 'privacy' => 'privacy',
            'terms' => 'terms', 'refund-policy' => 'legal.refunds', 'shipping-policy' => 'legal.shipping', 'calculators' => 'tools.index', 'panchang' => 'panchang',
            'festivals' => 'festivals', 'muhurat' => 'muhurat.index', 'store' => 'store.index', 'blog' => 'blog', 'rashifal' => 'rashifal'];
        $key = collect(array_keys($map))->first(fn ($k) => str_contains(mb_strtolower($page), $k)) ?? null;
        if (! $key || ! Route::has($map[$key])) {
            return ['error' => 'Unknown page.', 'pages' => array_keys($map)];
        }
        $url = route($map[$key]);
        $this->actions[] = ['label' => 'Open '.str_replace('-', ' ', $key), 'url' => $url, 'type' => 'page', 'page' => $key];

        return ['url' => $url];
    }

    private function birthProfiles(): array
    {
        return ['profiles' => BirthProfile::where('user_id', $this->user->id)->orderBy('id')->get()->map(fn ($p) => ['ref' => $p->ref, 'name' => $p->name,
            'relation' => BirthProfile::LABELS[$p->label] ?? $p->label, 'birth_date' => $p->birth_date?->format('Y-m-d')])->all()];
    }

    private function activity(): array
    {
        $uid = $this->user->id;
        $orders = DB::table('orders')->where('user_id', $uid)->orderByDesc('id')->limit(6)->get(['ref', 'kind', 'status', 'subtotal_minor', 'snapshot', 'created_at'])
            ->map(fn ($o) => ['ref' => $o->ref, 'item' => Checkout::label($o->kind, json_decode($o->snapshot, true) ?: []), 'status' => $o->status,
                'amount' => Money::format((int) $o->subtotal_minor), 'date' => substr((string) $o->created_at, 0, 10), 'next_step' => Checkout::next($o)['type'] ?? null]);
        $bookings = DB::table('bookings')->where('user_id', $uid)->whereIn('status', ['confirmed', 'completed'])->orderByDesc('start_utc')->limit(4)->get(['ref', 'status', 'mode', 'start_utc'])
            ->map(fn ($b) => ['ref' => $b->ref, 'status' => $b->status, 'mode' => $b->mode, 'when_ist' => CarbonImmutable::parse($b->start_utc, 'UTC')->setTimezone('Asia/Kolkata')->format('D d M Y, g:i A')]);
        $this->actions[] = ['label' => 'My account', 'url' => route('account.dashboard'), 'type' => 'page', 'page' => 'account'];

        return ['orders' => $orders->all(), 'bookings' => $bookings->all(),
            'pujas' => DB::table('puja_bookings')->where('user_id', $uid)->orderByDesc('id')->limit(4)->get(['ref', 'status', 'scheduled_at'])->all(),
            'reports' => DB::table('report_jobs')->where('user_id', $uid)->orderByDesc('id')->limit(4)->get(['ref', 'offering_slug', 'status'])->all(),
            'palm_readings' => DB::table('palm_readings')->where('user_id', $uid)->orderByDesc('id')->limit(2)->get(['ref', 'status'])->all()];
    }

    private function payAction(object $order): array
    {
        $url = route('checkout.show', $order->ref);
        $this->actions[] = ['label' => 'Pay securely '.Money::format((int) $order->payable_minor), 'url' => $url, 'type' => 'pay', 'order_ref' => $order->ref];

        return ['order_ref' => $order->ref, 'amount_to_pay' => Money::format((int) $order->payable_minor), 'status' => $order->status, 'pay_link' => $url,
            'note' => $order->status === 'paid' ? 'Already paid from wallet.' : 'The customer must tap the Pay button below the reply. The time is held for '.config('booking.hold_minutes').' minutes.'];
    }

    private function book(string $ref, string $start, ?string $profileRef, ?string $topic): array
    {
        $v = $this->variant($ref);
        if (! $v || ! Slots::bookable($v)) {
            return ['error' => 'This option is not booked by time.'];
        }
        if (Practitioners::TIER_OFFERINGS[$v->offering->slug] ?? null) {
            return ['error' => 'Team astrologer bookings are made on the booking page.', 'link' => route('account.book', $v->ref)];
        }
        $profile = $profileRef ? BirthProfile::where('user_id', $this->user->id)->where('ref', $profileRef)->value('id') : null;
        try {
            [$booking, $r] = app(Slots::class)->hold($this->user, $v, $start, $profile, $topic ? mb_substr($topic, 0, 1000) : null, 'ai:'.Str::ulid());
        } catch (ValidationException $e) {
            return ['error' => collect($e->errors())->flatten()->first()];
        }

        return ['booked_for_ist' => CarbonImmutable::parse($booking->start_utc, 'UTC')->setTimezone(Slots::TZ)->format('l, d M Y, g:i A')] + $this->payAction($r['order']);
    }

    private function startOrder(string $ref): array
    {
        $v = $this->variant($ref);
        if (! $v || ! $v->price_minor) {
            return ['error' => 'Option not available.'];
        }
        if (Slots::bookable($v)) {
            return ['error' => 'This is a call option: use free_times and book_consultation.'];
        }
        if ($kind = ServiceDetails::kind($v)) {
            // Details (palm photos, sankalp, birth details, visit address) are filled in before paying.
            $what = ['palm' => 'upload the two palm photos', 'puja' => 'add the names and gotra for the sankalp', 'report' => 'choose the birth details', 'question' => 'write the question', 'visit' => 'add the address and preferred date'][$kind];
            $this->actions[] = ['label' => 'Fill details and pay '.Money::format((int) $v->price_minor), 'url' => route('checkout.details', $v->ref), 'type' => 'details', 'slug' => $v->offering->slug, 'option_ref' => $v->ref];

            return ['next' => "The customer must tap the button below the reply to $what and then pay ".Money::format((int) $v->price_minor).'. Nothing is charged before that.'];
        }
        try {
            $r = app(Checkout::class)->startServiceOrder($this->user, $v, false, 'ai:'.Str::ulid());
        } catch (\RuntimeException|ValidationException $e) {
            return ['error' => 'Online payment is not available right now. Offer the request form or the office phone.'];
        }

        return $this->payAction($r['order']);
    }
}
