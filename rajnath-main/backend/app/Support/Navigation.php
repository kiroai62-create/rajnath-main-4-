<?php

namespace App\Support;

use App\Domain\Astro\Vedic;
use App\Domain\Legal\LegalDocuments;
use App\Domain\Puja\PujaRequests;
use App\Domain\Showcase\Profile;
use App\Domain\Store\Store;
use App\Http\Controllers\Web\ContentController;
use App\Models\Offering;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * The public site's main menu: six mega-menu panels built from the live catalogue, so a service
 * that is switched off disappears from the menu and a new one appears without code changes.
 * Cached for 10 minutes per language.
 *
 * Panel shape: ['key', 'label', 'href', 'columns' => [['title', 'links' => [['label', 'href', 'note']]]], 'feature' => [...]|null]
 */
class Navigation
{
    public static function forget(): void
    {
        foreach (array_keys(config('locales.available')) as $l) {
            Cache::forget('nav.menu.v1.'.$l);
        }
    }

    /** @return list<array<string, mixed>> */
    public static function menu(): array
    {
        return Cache::remember('nav.menu.v1.'.app()->getLocale(), 600, fn () => self::build());
    }

    /** @return Collection<int, Offering> */
    private static function offerings(array $families, int $limit): Collection
    {
        return Offering::public()->with(['translations', 'variants', 'family'])->whereHas('family', fn ($q) => $q->whereIn('code', $families))
            ->orderByDesc('featured')->orderBy('sort')->limit($limit)->get();
    }

    private static function links(Collection $offerings): array
    {
        return $offerings->map(fn (Offering $o) => [$o->t()?->title ?? $o->slug, route('services.show', $o->slug),
            ($p = $o->fromPriceMinor()) ? __('from :price', ['price' => Money::format($p)]) : null])->values()->all();
    }

    private static function price(string $slug): ?string
    {
        $p = Offering::public()->with('variants')->where('slug', $slug)->first()?->fromPriceMinor();

        return $p ? Money::format($p) : null;
    }

    private static function build(): array
    {
        $consult = self::offerings(['consultation', 'vastu', 'written_question'], 10);
        $reports = self::offerings(['report'], 12);
        $bundles = self::offerings(['bundle'], 5);
        $remedies = self::offerings(['remedy'], 4);
        $signs = ContentController::signSlugs();
        $signNames = array_map(fn ($s) => strtok($s, ' '), Vedic::SIGNS);
        $photo = app(Profile::class)->photo('profile.photo_about');

        return array_values(array_filter([
            [
                'key' => 'consult', 'label' => __('Consultations'), 'href' => route('services.index', ['family' => 'consultations']),
                'columns' => [
                    [__('Talk to an astrologer'), self::links($consult->whereIn('family.code', ['consultation', 'written_question'])->take(8))],
                    [__('Vastu and home'), array_merge(self::links($consult->where('family.code', 'vastu')), [
                        [__('All Vastu services'), route('services.index', ['family' => 'vastu']), null],
                        [__('Vastu articles'), route('blog', ['category' => 'vastu']), null],
                    ])],
                ],
                'feature' => ['img' => $photo, 'title' => __('Speak with Acharya Ji'), 'text' => __('One-to-one by audio, video or in person in Patna.'),
                    'href' => route('services.show', 'consultation-with-acharya-ji'), 'cta' => ($p = self::price('consultation-with-acharya-ji')) ? __('Book from :price', ['price' => $p]) : __('Book now')],
            ],
            $reports->isEmpty() ? null : [
                'key' => 'reports', 'label' => __('Reports'), 'href' => route('services.index', ['family' => 'reports']),
                'columns' => [
                    [__('Personal reports'), self::links($reports->take(6))],
                    [__('More reports'), self::links($reports->slice(6))],
                    [__('Value bundles'), array_merge(self::links($bundles), [[__('All reports and bundles'), route('services.index', ['family' => 'reports']), null]])],
                ],
                'feature' => ['img' => 'images/art/premium-personalized-kundli.jpg', 'title' => __('Premium Personalized Kundli'), 'text' => __('Your complete chart, dashas and remedies as a PDF in 3–5 days.'),
                    'href' => route('services.show', 'premium-personalized-kundli'), 'cta' => ($p = self::price('premium-personalized-kundli')) ? __('Get it for :price', ['price' => $p]) : __('See details')],
            ],
            [
                'key' => 'puja', 'label' => __('Puja & remedies'), 'href' => route('puja-request.create'),
                'columns' => array_values(array_filter([
                    [__('Puja on request'), array_merge(
                        [[__('Request a puja'), route('puja-request.create'), __('Tell us what you need; we call you to fix the date')]],
                        array_map(fn ($t) => [__($t), route('puja-request.create', ['puja' => $t]), null], array_slice(app(PujaRequests::class)->types(), 0, 6)),
                    )],
                    [__('Gems and samagri'), array_merge(
                        array_map(fn ($k) => [__(Store::CATEGORIES[$k]), route('store.index', ['category' => $k]), null], ['gemstone', 'rudraksha', 'yantra', 'mala', 'kit']),
                        self::links($remedies->take(3)),
                    )],
                ])),
                'feature' => ['img' => 'images/art/family-puja.jpg', 'title' => __('Puja on request'), 'text' => __('Fill in one short form. Acharya Ji\'s team calls you to fix the muhurat, place and dakshina.'),
                    'href' => route('puja-request.create'), 'cta' => __('Request a puja')],
            ],
            [
                'key' => 'horoscope', 'label' => __('Horoscope'), 'href' => route('rashifal'),
                'columns' => [
                    [__('Rashifal'), [
                        [__('Today'), route('rashifal', ['period' => 'daily']), null], [__('This week'), route('rashifal', ['period' => 'weekly']), null],
                        [__('This month'), route('rashifal', ['period' => 'monthly']), null], [__('This year'), route('rashifal', ['period' => 'yearly']), null],
                        [__('Nakshatra horoscope'), route('rashifal.kind', 'nakshatra'), null], [__('Numerology horoscope'), route('rashifal.kind', 'numerology'), null],
                        [__('Chinese horoscope'), route('rashifal.kind', 'chinese'), null], [__('Tarot'), route('rashifal.kind', 'tarot'), null],
                    ]],
                    [__('Your sign'), array_map(fn ($i) => [__($signNames[$i]), route('rashi', $signs[$i]), null], range(0, 11)), 'grid'],
                    [__('Sky today'), [
                        [__('Panchang'), route('panchang'), __('Tithi, Rahu Kaal, Choghadiya')], [__('Grah gochar'), route('gochar'), __('Planetary transits')],
                        [__('The nine planets'), route('navagraha'), null], [__('Shubh muhurat'), route('muhurat.index'), null],
                        [__('Festivals'), route('festivals'), null], [__('Vrat calendar'), route('calendar'), null],
                        [__('Learn Jyotish'), route('learn'), __('A guide to Vedic astrology')],
                    ]],
                ],
                'feature' => null,
            ],
            [
                'key' => 'tools', 'label' => __('Free tools'), 'href' => route('tools.index'),
                'columns' => [
                    [__('Kundli'), [
                        [__('Free Kundli'), route('tools.show', 'kundli'), __('Chart, Navamsa, dasha')], [__('Kundli matching'), route('tools.show', 'kundli-matching'), __('36 guna milan')],
                        [__('Moon sign'), route('tools.show', 'moon-sign'), null], [__('Nakshatra'), route('tools.show', 'nakshatra'), null],
                        [__('Lagna & Navamsa'), route('tools.show', 'lagna-navamsa'), null], [__('Lal Kitab chart'), route('tools.show', 'lal-kitab'), null],
                    ]],
                    [__('Doshas and timing'), [
                        [__('Sade Sati'), route('tools.show', 'sade-sati'), null], [__('Rahu–Ketu'), route('tools.show', 'rahu-ketu'), null],
                        [__('Pitra Dosh'), route('tools.show', 'pitra-dosh'), null], [__('Memorable periods (dasha)'), route('tools.show', 'memorable-periods'), null],
                        [__('Baby name letters'), route('tools.show', 'baby-name'), null], [__('Rudraksha suggestion'), route('tools.show', 'rudraksha'), null],
                    ]],
                    [__('Numerology'), [
                        [__('Numerology'), route('tools.show', 'numerology'), null], [__('Lo Shu grid'), route('tools.show', 'lo-shu-grid'), null],
                        [__('Name number'), route('tools.show', 'name-number'), null], [__('Lucky colours'), route('tools.show', 'lucky-colour'), null],
                        [__('Favourable dates'), route('tools.show', 'lucky-dates'), null], [__('Vehicle number'), route('tools.show', 'vehicle-number'), null],
                    ]],
                ],
                'feature' => ['img' => 'images/art/calculators.jpg', 'title' => __('26 free calculators'), 'text' => __('Calculated with the Swiss Ephemeris for your exact place and time.'),
                    'href' => route('tools.index'), 'cta' => __('See all tools')],
            ],
            [
                'key' => 'about', 'label' => __('About'), 'href' => route('about'),
                'columns' => [
                    [__('Acharya Ji'), [
                        [__('About Acharya Ji'), route('about'), null], [__('Awards and honours'), route('awards'), null],
                        [__('Gallery'), route('gallery'), __('Pujas, events, media')], [__('Client reviews'), route('reviews'), null],
                    ]],
                    [__('Learn'), [
                        [__('Learn Jyotish'), route('learn'), __('Houses, nakshatras, yogas, doshas')],
                        [__('Articles'), route('blog'), null], [__('Courses'), route('courses'), __('Coming soon')],
                        [__('Questions and answers'), route('faq'), null], [__('Ask our assistant'), route('assistant'), null],
                    ]],
                    [__('Help'), array_values(array_filter([
                        [__('Contact and timings'), route('contact'), null],
                        app(LegalDocuments::class)->isPublished('refunds') ? [__('Cancellation and refunds'), route('legal.refunds'), null] : null,
                        [__('Privacy policy'), route('privacy'), null], [__('Terms of use'), route('terms'), null],
                    ]))],
                ],
                'feature' => null,
            ],
        ]));
    }
}
