<?php

namespace App\Domain\Assistant;

use App\Domain\Astro\Vedic;
use App\Domain\Content\HoroscopeTypes;
use App\Http\Controllers\Web\ContentController;
use App\Models\User;
use App\Support\Business;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Answers visitors from the website's own information when no AI key is set up (or every AI
 * provider is failing): services and prices, booking steps, office address and hours, today's
 * Panchang, today's Rashifal, the free tools, FAQs, the owner's knowledge notes, store items and
 * articles. It uses the same lookups as the AI tools, so the facts are always current, and it
 * never invents anything: when it does not know, it points to the team.
 */
class SiteGuide
{
    private const WEST = ['aries', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'sagittarius', 'capricorn', 'aquarius', 'pisces'];

    /** Common spellings people type for the 12 Moon signs. */
    private const SIGN_WORDS = [
        ['mesh', 'mesha', 'मेष'], ['vrishabh', 'vrishabha', 'vrish', 'brish', 'वृषभ'], ['mithun', 'mithuna', 'मिथुन'], ['kark', 'karka', 'karkat', 'कर्क'],
        ['singh', 'simha', 'sinh', 'सिंह'], ['kanya', 'कन्या'], ['tula', 'तुला'], ['vrishchik', 'vrishchika', 'brishchik', 'वृश्चिक'],
        ['dhanu', 'धनु'], ['makar', 'makara', 'मकर'], ['kumbh', 'kumbha', 'कुंभ', 'कुम्भ'], ['meen', 'meena', 'मीन'],
    ];

    /** intent => regex (English, Hinglish and Hindi). Checked in this order. */
    private const INTENTS = [
        'greeting' => '/^(hi+|hello|hey|namaste|namaskar|pranam|pranaam|radhe radhe|jai shri ram|good (morning|evening|afternoon)|नमस्ते|प्रणाम|नमस्कार)\b[\s!.]*$/iu',
        'rashifal' => '/rashifal|horoscope|राशिफल|rashi ?phal|aaj ka (din|rashi)|zodiac|meri rashi|my sign/iu',
        'panchang' => '/panchang|पंचांग|tithi|तिथि|nakshatra today|aaj ka nakshatra|rahu ?kaal|राहु ?काल|choghadiya|चौघड़िया|sunrise|sunset|suryoday|aaj (kya|kaun si) tithi/iu',
        'contact' => '/address|location|kahan|kaha hai|where|office|contact|phone|mobile|number|whatsapp|timing|timings|open|closed|band|पता|समय|फोन|संपर्क/iu',
        'booking' => '/book|booking|appointment|consult|milna|milne|meet|baat karni|talk to|video call|audio call|session|परामर्श|बुक|मिलना/iu',
        'kundli' => '/kundli|kundali|janam ?patri|birth ?chart|कुंडली|janm|horoscope chart|lagna|dasha|दशा/iu',
        'matching' => '/match|milan|gun ?milan|guna|shaadi|marriage|vivah|विवाह|शादी|मिलान|manglik|मांगलिक/iu',
        'numerology' => '/numerology|ank ?jyotish|अंक|mulank|bhagyank|lo ?shu|name number|lucky number/iu',
        'puja' => '/puja|pooja|पूजा|path|paath|jaap|jap|havan|anushthan|chadhava|चढ़ावा|dosh|दोष|kaal ?sarp|mangal dosh|pitra|shanti/iu',
        'vastu' => '/vastu|वास्तु|ghar ka naksha|floor plan|direction|disha/iu',
        'gems' => '/gem|stone|ratna|रत्न|rudraksha|रुद्राक्ष|yantra|यंत्र|mala|samagri|store|shop|buy|kharid/iu',
        'payment' => '/refund|cancel|payment|paid|pay |upi|failed|invoice|gst|wallet|paisa wapas|भुगतान|रिफंड/iu',
        'about' => '/who is|about|acharya|rajnath|guru ?ji|pandit ?ji|qualification|experience|ph\.?d|award|सम्मान|आचार्य/iu',
        'festival' => '/festival|tyohar|त्योहार|vrat|व्रत|ekadashi|एकादशी|purnima|amavasya|diwali|holi|navratri|chhath|calendar/iu',
        'muhurat' => '/muhurat|मुहूर्त|shubh (din|samay|date)|griha pravesh|mundan|auspicious/iu',
        'palm' => '/palm|hast ?rekha|हस्तरेखा|haath/iu',
        'learn' => '/article|blog|learn|course|seekh|padh|explain|what is|kya hai|क्या है|matlab/iu',
    ];

    public function __construct(private ?User $user, private string $channel) {}

    /** @return array{reply: string, actions: list<array<string, string>>, handoff: bool} */
    public function answer(string $message): array
    {
        $tools = new AssistantTools($this->user, $this->channel);
        $m = mb_strtolower($message);
        $sign = $this->sign($m);
        $intent = collect(self::INTENTS)->keys()->first(fn ($k) => preg_match(self::INTENTS[$k], $m)) ?? ($sign !== null ? 'rashifal' : null);
        $handoff = false;
        try {
            $reply = match ($intent) {
                'greeting' => $this->greeting(),
                'rashifal' => $this->rashifal($tools, $sign, $m),
                'panchang' => $this->panchang($tools),
                'contact' => $this->contact($tools),
                'booking' => $this->booking($tools, $message),
                'kundli' => $this->tool($tools, 'kundli', 'Make your free Kundli (birth chart, Navamsa and Vimshottari dasha) with our calculator. For a full reading, Acharya Ji\'s Janma Kundli consultation and written reports are listed below.', 'kundli'),
                'matching' => $this->tool($tools, 'kundli-matching', 'Our free Kundli Matching tool gives the 36-guna Ashtakoota score with a Manglik check for both partners. For a detailed marriage reading, book a compatibility consultation.', 'marriage compatibility'),
                'numerology' => $this->tool($tools, 'numerology', 'Try our free numerology calculators: Mulank, Bhagyank, name number, Lo Shu grid, lucky dates and colours.', 'numerology'),
                'puja' => $this->pujaRequest(),
                'vastu' => $this->services($tools, 'vastu', 'Vastu guidance is available online from your floor plan or with a site visit in Patna. Options:'),
                'gems' => $this->store($tools, $message),
                'payment' => $this->payment($tools),
                'about' => $this->about($tools),
                'festival' => $this->page($tools, 'festivals', 'See the festival and vrat calendar for the year (dates by city), including Ekadashi, Purnima and Amavasya.', 'calendar'),
                'muhurat' => $this->page($tools, 'muhurat', 'Our Shubh Muhurat pages list good dates for vivah, griha pravesh, mundan, vehicle purchase and more, calculated from the Panchang.'),
                'palm' => $this->services($tools, 'palm', 'Palmistry readings are available from palm photos, on a video call or at the Patna office:'),
                'learn' => $this->learn($tools, $message),
                default => null,
            };
            $reply ??= $this->knowledge($tools, $message);
        } catch (Throwable $e) {
            report($e);
            $reply = null;
        }
        if ($reply === null) {
            $handoff = true;
            $reply = $this->fallback($tools);
        }

        return ['reply' => trim($reply), 'actions' => collect($tools->actions)->unique('url')->take(4)->values()->all(), 'handoff' => $handoff];
    }

    private function sign(string $m): ?int
    {
        foreach (self::WEST as $i => $w) {
            if (preg_match('/\b'.$w.'\b/u', $m)) {
                return $i;
            }
        }
        foreach (self::SIGN_WORDS as $i => $words) {
            foreach ($words as $w) {
                if (preg_match('/(^|[^\p{L}])'.preg_quote($w, '/').'($|[^\p{L}])/u', $m)) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function brand(string $key): string
    {
        return (string) (config('brand')[$key] ?? '');
    }

    private function greeting(): string
    {
        return 'Namaste! I am the website guide of '.$this->brand('brand.practitioner_name').'. Ask me about consultations and prices, today\'s Rashifal or Panchang, pujas, Vastu, gemstones, free Kundli, or how to book.';
    }

    private function rashifal(AssistantTools $tools, ?int $sign, string $m): string
    {
        $period = match (true) {
            (bool) preg_match('/week|hafte|saptahik|साप्ताहिक/u', $m) => 'weekly',
            (bool) preg_match('/month|mahina|mahine|masik|मासिक/u', $m) => 'monthly',
            (bool) preg_match('/year|saal|varshik|वार्षिक|20\d\d/u', $m) => 'yearly',
            default => 'daily',
        };
        if ($sign === null) {
            $tools->run('site_page', ['page' => 'rashifal']);

            return 'Tell me your Moon sign (for example "Mesha" or "Leo") and I will share its '.HoroscopeTypes::PERIODS[$period][1].' Rashifal. If you do not know your Moon sign, use the free Moon sign finder on the home page.';
        }
        $r = $tools->run('rashifal', ['sign' => self::WEST[$sign], 'period' => $period]);
        $tools->actions[] = ['label' => 'Open '.strtok(Vedic::SIGNS[$sign], ' ').' page', 'url' => route('rashi', ContentController::signSlugs()[$sign]), 'type' => 'page', 'page' => 'rashi'];
        $reading = collect($r['readings'] ?? [])->firstWhere('topic', 'general') ?? ($r['readings'][0] ?? null);
        if (! $reading) {
            return 'The '.strtolower(HoroscopeTypes::PERIODS[$period][1]).' Rashifal for '.Vedic::SIGNS[$sign].' is not ready yet. Please check the Rashifal page shortly.';
        }

        return Vedic::SIGNS[$sign].' — '.HoroscopeTypes::PERIODS[$period][1].":\n".Str::limit((string) $reading['body'], 900);
    }

    private function panchang(AssistantTools $tools): string
    {
        $p = $tools->run('panchang', []);
        if (isset($p['error'])) {
            return 'Today\'s Panchang is on the Panchang page.';
        }
        $t = fn (?string $iso) => $iso ? CarbonImmutable::parse($iso)->format('g:i A') : '—';
        $a = $p['angas'] ?? [];
        $rk = $p['intervals']['rahu_kaal'] ?? null;

        return "Today's Panchang for {$p['city']} ({$p['vara']}):\n"
            .'Tithi: '.($a['tithi'][0]['name'] ?? '—').(isset($a['tithi'][0]['ends']) ? ' till '.$t($a['tithi'][0]['ends']) : '')."\n"
            .'Nakshatra: '.($a['nakshatra'][0]['name'] ?? '—').(isset($a['nakshatra'][0]['ends']) ? ' till '.$t($a['nakshatra'][0]['ends']) : '')."\n"
            .'Yoga: '.($a['yoga'][0]['name'] ?? '—')."\n"
            .'Sunrise '.$t($p['sunrise'] ?? null).', sunset '.$t($p['sunset'] ?? null)."\n"
            .($rk ? 'Rahu Kaal: '.$t($rk['start']).' – '.$t($rk['end'])."\n" : '')
            .'Moon sign: '.($p['moon_sign'] ?? '—').'. For another city or date, open the full Panchang.';
    }

    private function contact(AssistantTools $tools): string
    {
        $tools->run('site_page', ['page' => 'contact']);

        return 'Office: '.app(Business::class)->addressLine().".\n"
            .'Hours: Mon–Wed and Fri–Sun, 8 AM–12 PM and 3 PM–7 PM (IST). Thursday closed.'."\n"
            .'Phone / WhatsApp: '.$this->brand('contact.phone').' · Email: '.$this->brand('contact.email')."\n"
            .'Consultations are also available by video or audio call from anywhere.';
    }

    /** Pujas are arranged on request: the visitor fills in the form and the team calls back. */
    private function pujaRequest(): string
    {
        return 'Acharya Ji\'s team performs pujas with full vidhi on a shubh muhurat, in your name and gotra: online by live video, at your home or at a temple. '
            .'Fill in the puja request form at '.route('puja-request.create').' with the puja, purpose and preferred date. The team calls you within one working day to fix the date, place and dakshina. Nothing is charged online.';
    }

    private function booking(AssistantTools $tools, string $message): string
    {
        $list = $this->serviceLines($tools, preg_match('/vastu|puja|pooja|report|palm|question/iu', $message) ? $message : 'consultation', 4);

        return "To book: open a service, choose the option (video, audio or in person), pick a free time and pay securely online. You get a confirmation by SMS, email and in your account.\n"
            .($list ? "Popular options:\n$list\n" : '')
            .($this->user ? '' : 'You will be asked to sign in with a one-time code before paying. ')
            .'Need help? Call or WhatsApp '.$this->brand('contact.phone').'.';
    }

    private function serviceLines(AssistantTools $tools, string $query, int $n = 5): string
    {
        $r = $tools->run('search_services', ['query' => $query]);
        $rows = array_slice($r['services'] ?? [], 0, $n);
        foreach (array_slice($rows, 0, 3) as $s) {
            $tools->actions[] = ['label' => Str::limit((string) $s['title'], 34), 'url' => route('services.show', $s['slug']), 'type' => 'service', 'slug' => $s['slug']];
        }

        return collect($rows)->map(fn ($s) => '• '.$s['title'].' — from '.$s['from_price'])->implode("\n");
    }

    private function services(AssistantTools $tools, string $query, string $intro): ?string
    {
        $lines = $this->serviceLines($tools, $query);
        if ($lines === '') {
            $lines = $this->serviceLines($tools, Str::before($intro, ' '));
        }
        $tools->actions[] = ['label' => 'All services', 'url' => route('services.index'), 'type' => 'page', 'page' => 'services'];

        return $intro."\n".($lines ?: 'Please see all services for the full list.');
    }

    private function tool(AssistantTools $tools, string $slug, string $text, string $service): string
    {
        $tools->actions[] = ['label' => 'Open the free tool', 'url' => route('tools.show', $slug), 'type' => 'page', 'page' => 'tools'];
        $lines = $this->serviceLines($tools, $service, 3);

        return $text.($lines ? "\n".$lines : '');
    }

    private function page(AssistantTools $tools, string $page, string $text, ?string $route = null): string
    {
        if ($route) {
            $tools->actions[] = ['label' => 'Open the calendar', 'url' => route($route), 'type' => 'page', 'page' => $route];
        }
        $tools->run('site_page', ['page' => $page]);

        return $text;
    }

    private function store(AssistantTools $tools, string $message): string
    {
        $words = preg_replace('/\b(buy|kharidna|chahiye|price|kitna|ka|ki|ke|the|a|i|want|need|store|shop)\b/iu', ' ', $message);
        $r = $tools->run('search_store', ['query' => trim(preg_replace('/\s+/', ' ', $words))]);
        $rows = $r['products'] ?? [];
        if ($rows === []) {
            $r = $tools->run('search_store', ['query' => '']);
            $rows = $r['products'] ?? [];
        }
        $tools->actions[] = ['label' => 'Open the store', 'url' => route('store.index'), 'type' => 'page', 'page' => 'store'];
        $list = collect(array_slice($rows, 0, 5))->map(fn ($p) => '• '.$p['name'].' — '.$p['price'].($p['in_stock'] ? '' : ' (out of stock)'))->implode("\n");

        return "Certified gemstones, rudraksha, yantras and puja samagri are in our store. Acharya Ji advises wearing a gemstone only after your chart has been checked.\n".$list;
    }

    private function payment(AssistantTools $tools): string
    {
        $tools->run('site_page', ['page' => 'refund-policy']);
        $faq = $this->bestFaq('refund cancel payment reschedule');

        return ($faq ? $faq."\n\n" : '').'For a payment or refund problem, please contact the office on '.$this->brand('contact.phone').' or '.$this->brand('contact.email').' with your order number, and the team will help. Payments are only taken on our secure checkout; we never ask for OTPs or card details in chat.';
    }

    private function about(AssistantTools $tools): string
    {
        $tools->run('site_page', ['page' => 'about']);
        $tools->run('site_page', ['page' => 'awards']);

        return $this->brand('brand.practitioner_name').' holds a Ph.D. in Astrology (K.S.D.S.U., Darbhanga) and is the Director of Jyotirveda Vigyan Sansthan, Patna, with more than 30 years of practice in Jyotish, Vastu, numerology and palmistry. '
            .'He served as Jyotish and Karmkand consultant at Jyotish Mandap, Mahavir Mandir Trust, Patna (2002–2016), and has been honoured in India, Nepal, Malaysia and Sri Lanka, including the IVAF Professor Award 2026. '
            .'He consults in Hindi, Maithili, Sanskrit and English.';
    }

    private function learn(AssistantTools $tools, string $message): ?string
    {
        $k = $this->knowledge($tools, $message);
        if ($k !== null) {
            return $k;
        }
        $r = $tools->run('search_articles', ['query' => $this->keywords($message)]);
        $rows = array_slice($r['articles'] ?? [], 0, 3);
        foreach ($rows as $a) {
            $tools->actions[] = ['label' => Str::limit((string) $a['title'], 34), 'url' => $a['url'], 'type' => 'page', 'page' => 'blog'];
        }

        return $rows ? "These articles explain it:\n".collect($rows)->map(fn ($a) => '• '.$a['title'].' — '.Str::limit((string) $a['summary'], 120))->implode("\n") : null;
    }

    private function keywords(string $message): string
    {
        $stop = ['what', 'is', 'the', 'a', 'an', 'of', 'kya', 'hai', 'ka', 'ki', 'ke', 'me', 'mein', 'and', 'or', 'how', 'to', 'do', 'i', 'my', 'for', 'about', 'please', 'tell', 'batao', 'bataiye', 'kaise', 'kab', 'kyon'];
        $w = array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($message)) ?: [], fn ($x) => mb_strlen($x) > 2 && ! in_array($x, $stop, true));

        return implode(' ', array_slice(array_values($w), 0, 4));
    }

    /** Best FAQ / office note for the message, by shared words. */
    private function bestFaq(string $message): ?string
    {
        $words = array_unique(array_filter(explode(' ', $this->keywords($message))));
        if ($words === []) {
            return null;
        }
        $score = function (string $text) use ($words) {
            $t = mb_strtolower($text);

            return collect($words)->sum(fn ($w) => str_contains($t, $w) ? 1 : 0);
        };
        $best = null;
        $bestScore = 0;
        foreach (DB::table('faqs')->where('published', true)->where('locale', 'en')->get(['question', 'answer']) as $f) {
            $s = $score($f->question) * 2 + $score($f->answer);
            if ($s > $bestScore) {
                [$best, $bestScore] = [Str::limit(trim(strip_tags($f->answer)), 700), $s];
            }
        }
        foreach (DB::table('assistant_knowledge')->where('active', true)->get(['title', 'body']) as $k) {
            $s = $score($k->title) * 2 + $score($k->body);
            if ($s > $bestScore) {
                [$best, $bestScore] = [Str::limit(trim(strip_tags($k->body)), 700), $s];
            }
        }

        return $bestScore >= min(2, count($words)) ? $best : null;
    }

    private function knowledge(AssistantTools $tools, string $message): ?string
    {
        if ($faq = $this->bestFaq($message)) {
            $tools->run('site_page', ['page' => 'faq']);

            return $faq;
        }
        $lines = $this->serviceLines($tools, $this->keywords($message), 4);

        return $lines !== '' ? "These services match your question:\n".$lines : null;
    }

    private function fallback(AssistantTools $tools): string
    {
        $tools->actions[] = ['label' => 'All services', 'url' => route('services.index'), 'type' => 'page', 'page' => 'services'];
        $tools->run('site_page', ['page' => 'contact']);

        return 'I could not find that on our website. You can ask me about consultations and prices, Rashifal, Panchang, pujas, Vastu, gemstones or booking — or talk to the team directly on '
            .$this->brand('contact.phone').' (phone / WhatsApp).';
    }
}
