<?php

namespace App\Domain\Store;

use Database\Seeders\StoreSeeder;
use Illuminate\Support\Str;

/**
 * The detailed "product page" content for the store: highlights, benefits, who it suits, how to
 * wear or use it, possible side effects and cautions, care, specifications and FAQs. Built from
 * the product's category plus per-item traditional details (planet, metal, finger, day, mantra),
 * so every product has a complete page without hand-written HTML. The owner's own description,
 * weight, origin and certificate (Admin › Gem store) are shown alongside and always win.
 * Shared by the website (store/show) and the app (GET /api/v1/store/products/{slug} → guide).
 */
class ProductGuide
{
    /** Navratna and uparatna: [planet, metal, finger, day, mantra, min weight, do-not-combine, key benefits, suits]. */
    public const GEMS = [
        'ruby-manik' => ['Sun (Surya)', 'Gold or copper', 'Ring finger, right hand', 'Sunday, within two hours after sunrise', 'Om Hraam Hreem Hraum Sah Suryaya Namah', '3 ratti (about 2.7 carat)', 'Blue sapphire, hessonite, cat\'s eye, diamond',
            ['Confidence and leadership', 'Recognition at work and with government', 'Stronger will-power and self-esteem', 'Better relations with father and seniors'], 'People with Leo or Aries lagna, or a well-placed but weak Sun; government service, politics, administration'],
        'pearl-moti' => ['Moon (Chandra)', 'Silver', 'Little finger, right hand', 'Monday, in the evening (Shukla paksha)', 'Om Shraam Shreem Shraum Sah Chandraya Namah', '4 ratti (about 3.6 carat)', 'Hessonite, cat\'s eye, blue sapphire',
            ['Calm mind and emotional balance', 'Better sleep and less anxiety', 'Harmony with mother and family', 'Helps people who feel restless or over-think'], 'People with Cancer lagna, or a weak or afflicted Moon; counsellors, nurses, hospitality'],
        'red-coral-moonga' => ['Mars (Mangal)', 'Gold, copper or silver', 'Ring finger, right hand', 'Tuesday, one hour after sunrise', 'Om Kraam Kreem Kraum Sah Bhaumaya Namah', '6 ratti (about 5.5 carat)', 'Emerald, diamond, hessonite, cat\'s eye',
            ['Courage, energy and stamina', 'Help in property, land and debt matters', 'Traditional remedy for Mangal dosh (after chart check)', 'Discipline for sports, police and army careers'], 'People with Aries or Scorpio lagna, or a weak Mars; engineers, surgeons, sportspeople, police'],
        'emerald-panna' => ['Mercury (Budh)', 'Gold or silver', 'Little finger, right hand', 'Wednesday, two hours after sunrise', 'Om Braam Breem Braum Sah Budhaya Namah', '3 ratti (about 2.7 carat)', 'Red coral, pearl',
            ['Clear speech and quicker learning', 'Business, trade and communication', 'Memory and concentration for students', 'Calm nerves'], 'People with Gemini or Virgo lagna; students, writers, accountants, traders, IT and media'],
        'yellow-sapphire-pukhraj' => ['Jupiter (Guru)', 'Gold', 'Index finger, right hand', 'Thursday, in the morning', 'Om Graam Greem Graum Sah Gurave Namah', '3 ratti (about 2.7 carat)', 'Diamond, emerald, blue sapphire, hessonite',
            ['Wisdom and good judgement', 'Traditionally worn for timely marriage and children', 'Prosperity and respect', 'Support for teachers, advisers and lawyers'], 'People with Sagittarius or Pisces lagna, or a weak Jupiter; teachers, lawyers, bankers, priests'],
        'diamond-heera' => ['Venus (Shukra)', 'Platinum, white gold or silver', 'Middle or ring finger, right hand', 'Friday, in the morning', 'Om Draam Dreem Draum Sah Shukraya Namah', '0.25 carat (natural)', 'Ruby, pearl, yellow sapphire, red coral',
            ['Love, harmony and comfort in marriage', 'Creativity in art, music, fashion and design', 'Charm and social grace', 'Luxury and vehicles, in traditional belief'], 'People with Taurus or Libra lagna; artists, designers, actors, hospitality and luxury trades'],
        'blue-sapphire-neelam' => ['Saturn (Shani)', 'Silver, panchdhatu or white gold', 'Middle finger, right hand', 'Saturday, in the evening', 'Om Praam Preem Praum Sah Shanaye Namah', '3 ratti (about 2.7 carat)', 'Ruby, pearl, red coral, yellow sapphire',
            ['Fast results in career and discipline when it suits the chart', 'Relief during Sade Sati and Dhaiya, in traditional belief', 'Focus and long-term thinking', 'Protection from sudden setbacks'], 'People with Capricorn or Aquarius lagna, only after a chart check and a 3-day trial'],
        'hessonite-gomed' => ['Rahu', 'Silver or panchdhatu', 'Middle finger, right hand', 'Saturday, after sunset', 'Om Bhraam Bhreem Bhraum Sah Rahave Namah', '6 ratti (about 5.5 carat)', 'Ruby, pearl, red coral, yellow sapphire',
            ['Clarity when confused or misled', 'Help in politics, foreign travel and research', 'Protection from hidden enemies, in traditional belief', 'Calm during Rahu dasha'], 'People running Rahu dasha or antardasha with a supportive Rahu in the chart'],
        'cats-eye-lehsunia' => ['Ketu', 'Silver or panchdhatu', 'Middle or little finger, right hand', 'Tuesday or Saturday, before sunrise', 'Om Sraam Sreem Sraum Sah Ketave Namah', '5 ratti (about 4.5 carat)', 'Ruby, pearl, red coral, yellow sapphire',
            ['Intuition and spiritual focus', 'Protection from sudden losses and accidents, in traditional belief', 'Help in research and technical work', 'Calm during Ketu dasha'], 'People running Ketu dasha with a supportive Ketu; researchers, healers, spiritual seekers'],
        'opal' => ['Venus (uparatna)', 'Silver or white gold', 'Ring or index finger, right hand', 'Friday, in the morning', 'Om Shum Shukraya Namah', '5 ratti', 'Ruby, red coral',
            ['A gentler, more affordable substitute for diamond', 'Love, beauty and creativity', 'Emotional warmth'], 'People advised a Venus stone who prefer an affordable option'],
        'citrine-sunela' => ['Jupiter (uparatna)', 'Gold or silver', 'Index finger, right hand', 'Thursday, in the morning', 'Om Brim Brihaspataye Namah', '5 ratti', 'Diamond, blue sapphire',
            ['Affordable substitute for yellow sapphire', 'Optimism and confidence', 'Support for study and business'], 'People advised a Jupiter stone on a smaller budget'],
        'amethyst-katela' => ['Saturn (uparatna)', 'Silver', 'Middle finger, right hand', 'Saturday, in the evening', 'Om Sham Shanaischaraya Namah', '5 ratti', 'Ruby, pearl',
            ['Gentle substitute for blue sapphire', 'Calm mind and better sleep', 'Self-control and patience'], 'People advised a Saturn stone who are not ready for blue sapphire'],
        'peridot' => ['Mercury (uparatna)', 'Gold or silver', 'Little finger, right hand', 'Wednesday, in the morning', 'Om Bum Budhaya Namah', '5 ratti', 'Red coral, pearl',
            ['Substitute for emerald', 'Communication and learning', 'Freshness and positivity'], 'Students and professionals advised a Mercury stone on a budget'],
        'moonstone' => ['Moon (uparatna)', 'Silver', 'Little finger, right hand', 'Monday, in the evening', 'Om Som Somaya Namah', '5 ratti', 'Hessonite, cat\'s eye',
            ['Soft substitute for pearl', 'Emotional calm and intuition', 'Restful sleep'], 'People with a weak Moon who want a gentle stone'],
        'garnet-tamra' => ['Sun (uparatna)', 'Gold or copper', 'Ring finger, right hand', 'Sunday, after sunrise', 'Om Ghrinih Suryaya Namah', '5 ratti', 'Blue sapphire, hessonite',
            ['Substitute for ruby', 'Energy and confidence', 'Motivation at work'], 'People advised a Sun stone on a smaller budget'],
    ];

    /** Rudraksha by mukhi: [ruling deity, mantra, key benefits]. */
    public const RUDRAKSHA = [
        'one-mukhi-rudraksha' => ['Lord Shiva', 'Om Hreem Namah', ['Focus and meditation', 'Leadership and inner strength', 'Considered the most sacred bead']],
        'two-mukhi-rudraksha' => ['Ardhanarishwar (Shiva-Parvati)', 'Om Namah', ['Harmony between husband and wife', 'Emotional balance', 'Better relationships']],
        'three-mukhi-rudraksha' => ['Agni Dev', 'Om Kleem Namah', ['Confidence', 'Letting go of past guilt', 'Energy and less lethargy']],
        'four-mukhi-rudraksha' => ['Lord Brahma', 'Om Hreem Namah', ['Memory and concentration', 'Clear speech', 'Creativity for students and writers']],
        'five-mukhi-rudraksha' => ['Kalagni Rudra', 'Om Hreem Namah', ['Peace of mind', 'Daily jaap and meditation', 'Suits almost everyone']],
        'six-mukhi-rudraksha' => ['Lord Kartikeya', 'Om Hreem Hum Namah', ['Willpower', 'Charm and confidence', 'Balanced desires']],
        'seven-mukhi-rudraksha' => ['Goddess Mahalakshmi', 'Om Hum Namah', ['Financial stability', 'Patience during hard times', 'Business growth']],
        'eight-mukhi-rudraksha' => ['Lord Ganesha', 'Om Hum Namah', ['Removal of obstacles', 'Calm during Rahu periods', 'Success in new work']],
        'nine-mukhi-rudraksha' => ['Goddess Durga', 'Om Hreem Hum Namah', ['Courage and energy', 'Protection', 'Calm during Ketu periods']],
        'ten-mukhi-rudraksha' => ['Lord Vishnu', 'Om Hreem Namah Namah', ['Protection from negative energy', 'Help with Vastu dosh', 'Peaceful home']],
        'eleven-mukhi-rudraksha' => ['Ekadash Rudra (Hanuman)', 'Om Hreem Hum Namah', ['Fearlessness', 'Success and confidence', 'Protection while travelling']],
        'twelve-mukhi-rudraksha' => ['Surya Dev', 'Om Kraum Sraum Raum Namah', ['Leadership', 'Confidence in public life', 'Help in government work']],
        'thirteen-mukhi-rudraksha' => ['Kamdev', 'Om Hreem Namah', ['Attraction and charm', 'Comfort and fulfilment of wishes', 'Creativity']],
        'fourteen-mukhi-rudraksha-nepal' => ['Hanuman (Deva Mani)', 'Om Namah', ['Intuition and decision-making', 'Protection during Saturn\'s periods', 'Rare and highly valued']],
        'ganesh-rudraksha' => ['Lord Ganesha', 'Om Gam Ganapataye Namah', ['Success in new beginnings', 'Removal of obstacles', 'Help for students']],
        'gauri-shankar-rudraksha' => ['Shiva and Parvati', 'Om Gauri Shankaraya Namah', ['Harmony in marriage and family', 'Help for those seeking a life partner', 'Unity and understanding']],
    ];

    public const CATEGORY_LABELS = ['gemstone' => 'Gemstone', 'rudraksha' => 'Rudraksha', 'yantra' => 'Yantra', 'mala' => 'Mala & bracelet', 'kit' => 'Puja kit', 'samagri' => 'Puja samagri', 'idol' => 'Idol & shivling', 'vastu' => 'Vastu remedy'];

    /**
     * Full page content for one product row.
     *
     * @return array{category_label: string, highlights: list<string>, sections: list<array{title: string, kind: string, items: list<mixed>}>, faqs: list<array{q: string, a: string}>, specs: list<array{0: string, 1: string}>, credit: ?array<string, string>}
     */
    public static function for(object $p): array
    {
        $sections = match ($p->category) {
            'gemstone' => self::gemstone($p),
            'rudraksha' => self::rudraksha($p),
            'yantra' => self::yantra($p),
            'mala' => self::mala($p),
            'kit', 'samagri' => self::puja($p),
            'idol' => self::idol($p),
            'vastu' => self::vastu($p),
            default => [],
        };

        return [
            'category_label' => self::CATEGORY_LABELS[$p->category] ?? ucfirst((string) $p->category),
            'highlights' => self::highlights($p),
            'sections' => $sections,
            'faqs' => self::faqs($p),
            'specs' => self::specs($p),
            'credit' => self::credit($p),
        ];
    }

    /** @return list<string> */
    private static function highlights(object $p): array
    {
        $h = match ($p->category) {
            'gemstone' => ['100% natural, with a lab certificate', 'Chosen to suit your chart on request', 'Energised with mantra before dispatch'],
            'rudraksha' => ['Natural bead, checked for mukhi and holes', 'Energised (pran pratishtha) before dispatch', 'Comes with wearing method and mantra'],
            'yantra' => ['Pure copper, engraved', 'Energised before dispatch', 'Comes with placement and puja method'],
            'mala' => ['Natural beads, hand-strung', 'Energised before dispatch', 'For daily jaap or wear'],
            'kit', 'samagri' => ['Pure, fresh puja items', 'Ready to use at home', 'Vidhi notes included where relevant'],
            'idol' => ['Solid, well finished', 'Suitable for the home temple', 'Packed securely'],
            'vastu' => ['Traditional Vastu remedy', 'No construction needed', 'Comes with placement guidance'],
            default => [],
        };

        return [...$h, 'Insured, tracked shipping across India'];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function gemstone(object $p): array
    {
        $g = self::GEMS[$p->slug] ?? null;
        if (! $g) {
            return [];
        }
        [$planet, $metal, $finger, $day, $mantra, $min, $avoid, $benefits, $suits] = $g;

        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => $benefits],
            ['title' => 'Who should wear it', 'kind' => 'list', 'items' => [$suits, 'Only when the planet is a friend (yogkaraka or benefic) in your birth chart — Acharya Ji checks this before recommending.']],
            ['title' => 'How to wear', 'kind' => 'table', 'items' => [
                ['Planet', $planet], ['Metal', $metal], ['Finger', $finger], ['Day and time', $day], ['Minimum weight', $min.' — or as advised for your body weight'],
                ['Mantra', $mantra.' (108 times)'],
                ['Method', 'Dip the ring in raw milk and Gangajal, light a diya, chant the mantra 108 times, then wear it facing east.'],
            ]],
            ['title' => 'Possible side effects and cautions', 'kind' => 'list', 'items' => [
                'A stone for a planet that is unfavourable in your chart can bring stress, conflict or setbacks instead of help. Do not wear it on someone else\'s advice without a chart check.',
                'Do not wear it together with: '.$avoid.'.',
                $p->slug === 'blue-sapphire-neelam' ? 'Blue sapphire acts fast. Wear it on trial for three days first; remove it if you notice accidents, bad dreams or sudden losses.' : 'If you feel restless, unwell or notice repeated problems after wearing it, take it off and consult us.',
                'Heated, glass-filled or synthetic stones are not considered effective. Buy only with a lab certificate.',
                'Gemstones are a traditional remedy. They do not replace medical, legal or financial advice.',
            ]],
            ['title' => 'Care', 'kind' => 'list', 'items' => ['Clean it gently with water and a soft cloth.', 'Remove it before heavy work, swimming or applying chemicals.', 'Re-energise it with the mantra on the planet\'s day once a month.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function rudraksha(object $p): array
    {
        [$deity, $mantra, $benefits] = self::RUDRAKSHA[$p->slug] ?? ['Lord Shiva', 'Om Namah Shivaya', [$p->summary]];

        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => $benefits],
            ['title' => 'Who should wear it', 'kind' => 'list', 'items' => ['Anyone, of any age or gender, who wants the qualities above.', 'Rudraksha has no "bad effects" from planets, so it is often suggested when a gemstone does not suit the chart.']],
            ['title' => 'How to wear', 'kind' => 'table', 'items' => [
                ['Ruling deity', $deity], ['Planet', (string) self::planetFrom($p)], ['Day', 'Monday morning, or on Shivratri / in Shravan'],
                ['Thread', 'Red or yellow silk thread, or a silver / gold cap'], ['Mantra', $mantra.' and Om Namah Shivaya (108 times)'],
                ['Method', 'Wash it with Gangajal and raw milk, offer it to a shivling, chant the mantra, then wear it so it touches the skin.'],
            ]],
            ['title' => 'Cautions', 'kind' => 'list', 'items' => [
                'Traditionally it is removed before sleeping, bathing with soap, and when visiting a cremation ground.',
                'Do not lend your rudraksha to others.',
                'Beware of fake beads with carved or glued faces. Ours are checked for natural mukhi and holes.',
                'A rudraksha is a spiritual aid and does not replace medical treatment.',
            ]],
            ['title' => 'Care', 'kind' => 'list', 'items' => ['Oil it lightly with sandalwood or mustard oil once a month.', 'Keep it dry and away from perfume and soap.', 'Keep it in the puja place when not worn.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function yantra(object $p): array
    {
        $for = self::planetFrom($p);

        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => [$p->summary, 'A daily point of focus for prayer and mantra.', 'Brings the blessings of '.$for.' to the home or workplace, in traditional belief.']],
            ['title' => 'Where and how to place it', 'kind' => 'table', 'items' => [
                ['Direction', 'North-east of the home or office, or in the puja place, facing east or north'],
                ['Height', 'At or above eye level, on a clean cloth or stand'],
                ['Day to install', 'An auspicious day such as Thursday, Friday or the deity\'s day, in Shukla paksha'],
                ['Method', 'Wash it with Gangajal, apply kumkum and chandan, offer flowers and a diya, and chant the deity\'s mantra 108 times.'],
            ]],
            ['title' => 'Cautions', 'kind' => 'list', 'items' => ['Do not place it in a bathroom, bedroom floor or under stairs.', 'Do not keep a broken or bent yantra; replace it.', 'A yantra supports prayer and effort; it does not replace them.']],
            ['title' => 'Care', 'kind' => 'list', 'items' => ['Clean the copper with lemon and salt if it darkens.', 'Light a diya in front of it daily if you can.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function mala(object $p): array
    {
        $bracelet = str_contains(strtolower($p->name), 'bracelet');

        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => [$p->summary, $bracelet ? 'Easy to wear daily on the wrist.' : 'Keeps count during mantra jaap so the mind can stay on the mantra.', 'Associated with '.self::planetFrom($p).', in traditional belief.']],
            ['title' => $bracelet ? 'How to wear' : 'How to use for jaap', 'kind' => 'list', 'items' => $bracelet
                ? ['Wear it on the right wrist after washing it with Gangajal and chanting your mantra.', 'Remove it while bathing, sleeping and doing heavy work.']
                : ['Hold the mala in the right hand over the middle finger; move the beads with the thumb.', 'Start from the bead next to the sumeru (guru bead) and do not cross the sumeru; turn back at the end.', 'Keep the mala covered in a cloth bag (gomukhi) and do not show it around.']],
            ['title' => 'Cautions', 'kind' => 'list', 'items' => ['Keep a separate mala for jaap and for wearing.', 'Do not let the mala touch the floor.', 'Natural beads vary in size and colour.']],
            ['title' => 'Care', 'kind' => 'list', 'items' => ['Keep it away from water, soap and perfume.', 'Replace the thread if it becomes loose.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function puja(object $p): array
    {
        return [
            ['title' => 'What it is for', 'kind' => 'list', 'items' => [$p->summary, 'Saves you going from shop to shop before the puja.']],
            ['title' => 'How to use', 'kind' => 'list', 'items' => ['Keep the items in a clean place until the puja.', 'Follow the vidhi notes in the pack, or book a pandit through us to perform the puja in your name.', 'Use the items within the time printed on the pack.']],
            ['title' => 'Good to know', 'kind' => 'list', 'items' => ['Natural items vary slightly in colour and size.', 'If you need a particular item added, tell us before ordering.', 'Keep away from children and pets; do not eat samagri unless it is prasad.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function idol(object $p): array
    {
        $shiv = str_contains(strtolower($p->name), 'shivling');

        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => [$p->summary, 'A focus for daily worship and family prayer.']],
            ['title' => 'Where and how to place it', 'kind' => 'list', 'items' => $shiv
                ? ['Place the shivling on a jalahari (base) in the north-east, with the outlet facing north.', 'Offer water or raw milk with "Om Namah Shivaya" every morning.', 'At home a small shivling (up to a thumb\'s height) is traditionally preferred.']
                : ['Place it in the puja place in the north-east, facing east or west.', 'Keep it on a clean cloth on a raised stand, not on the floor.', 'Do pran pratishtha (energising) on an auspicious day before daily puja.']],
            ['title' => 'Care', 'kind' => 'list', 'items' => ['Clean brass with tamarind or lemon and dry it fully.', 'Do not keep broken idols in the puja place.']],
        ];
    }

    /** @return list<array{title: string, kind: string, items: list<mixed>}> */
    private static function vastu(object $p): array
    {
        return [
            ['title' => 'Benefits', 'kind' => 'list', 'items' => [$p->summary, 'Corrects a zone without breaking walls, in traditional Vastu.']],
            ['title' => 'How to use', 'kind' => 'list', 'items' => ['Use it where Acharya Ji or your Vastu report advises; the right zone matters more than the item.', 'Clean it with Gangajal and place it on an auspicious day.', 'Book a Vastu consultation if you are not sure where it should go.']],
            ['title' => 'Cautions', 'kind' => 'list', 'items' => ['Placing a remedy in the wrong zone may not help. Ask first.', 'Vastu remedies support, but do not replace, proper construction and repairs.']],
        ];
    }

    /** @return list<array{q: string, a: string}> */
    private static function faqs(object $p): array
    {
        $common = [
            ['q' => 'How long does delivery take?', 'a' => 'Orders are packed within 1–2 working days and usually reach you in 3–7 days anywhere in India, with tracking.'],
            ['q' => 'Can I return it?', 'a' => 'Unused items in their original packing can be returned as per our refund policy. Energised and custom items cannot be returned once dispatched.'],
        ];
        $specific = match ($p->category) {
            'gemstone' => [
                ['q' => 'Which gemstone is right for me?', 'a' => 'It depends on your birth chart, not your zodiac sign alone. Book a consultation or send your birth details, and Acharya Ji will advise the stone, weight and metal.'],
                ['q' => 'Is the stone natural?', 'a' => 'Yes. Every stone is natural and comes with a lab certificate. Photos are of the type of stone; the stone you receive is matched to the certificate.'],
                ['q' => 'Can I get it set in a ring?', 'a' => 'Yes. Tell us your ring size and metal after ordering and we will arrange it at extra cost.'],
            ],
            'rudraksha' => [
                ['q' => 'How do I know it is real?', 'a' => 'We check every bead for natural mukhi lines and the natural hole. You can test it with an X-ray or lab report; we will refund a bead that is not genuine.'],
                ['q' => 'Can women wear rudraksha?', 'a' => 'Yes. Rudraksha can be worn by anyone.'],
            ],
            'yantra' => [['q' => 'Is the yantra energised?', 'a' => 'Yes. It is energised with mantra in our puja before it is sent.']],
            default => [],
        };

        return [...$specific, ...$common];
    }

    /** @return list<array{0: string, 1: string}> */
    private static function specs(object $p): array
    {
        return array_values(array_filter([
            ['Category', self::CATEGORY_LABELS[$p->category] ?? ucfirst((string) $p->category)],
            ['Associated with', (string) self::planetFrom($p)],
            $p->weight ? ['Weight', $p->weight] : null,
            $p->origin ? ['Origin', $p->origin] : null,
            $p->certification ? ['Certificate', $p->certification] : null,
            ['Shipping', 'Free, insured and tracked within India'],
        ], fn ($r) => $r !== null && $r[1] !== ''));
    }

    /** Planet / deity from the store list (StoreSeeder::ITEMS), e.g. "Sun" or "Lakshmi". */
    private static function planetFrom(object $p): string
    {
        if (isset(self::GEMS[$p->slug])) {
            return self::GEMS[$p->slug][0];
        }
        foreach (StoreSeeder::ITEMS as $row) {
            if (Str::slug($row[1]) === $p->slug) {
                return $row[2];
            }
        }

        return 'Vedic tradition';
    }

    /**
     * Photo credit for the bundled store photo (Wikimedia Commons, see public/images/store/credits.json).
     * Null when the owner uploaded their own photo.
     *
     * @return array{author: string, license: string, license_url: string, source: string}|null
     */
    public static function credit(object $p): ?array
    {
        if (! is_string($p->image_path) || ! str_starts_with($p->image_path, 'images/store/')) {
            return null;
        }
        static $credits = null;
        $credits ??= is_file($f = public_path('images/store/credits.json')) ? (array) json_decode((string) file_get_contents($f), true) : [];
        $c = $credits[$p->slug] ?? null;

        return $c ? ['author' => (string) $c['author'], 'license' => (string) $c['license'], 'license_url' => (string) ($c['license_url'] ?? ''), 'source' => (string) $c['source']] : null;
    }
}
