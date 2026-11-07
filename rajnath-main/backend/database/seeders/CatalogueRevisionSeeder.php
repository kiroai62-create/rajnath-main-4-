<?php

namespace Database\Seeders;

use App\Models\Offering;
use App\Models\ServiceFamily;
use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Owner's catalogue changes of 5 November 2026. Runs once on every installation (new or
 * existing) and records a flag, so later changes made in Admin › Catalogue are kept.
 *
 * - Lal Kitab services and Pitra Dosh puja are retired (hidden; old orders keep working).
 * - Santan Badha → Santan Prapti, Shatru Nash / Shatru Badha / Nazar Dosh → Shatru Badha,
 *   Nasha Mukti Prarthana → Nasha Mukti Puja (old addresses redirect to the new ones).
 * - New: Durga Saptashati Path, Shrimad Bhagavad Gita Path, Gand Mool Nakshatra Shanti,
 *   Griha Pravesh, Griha Aarambh (Bhoomi Pujan), Nyayik Uljhan (court case) Nivaran Puja,
 *   Tarot Reading.
 * - Palmistry: one service for everyone, online by photos or video, or in person in Patna.
 * - Vastu site visits priced by place; new report and puja prices.
 */
class CatalogueRevisionSeeder extends Seeder
{
    public const FLAG = 'catalogue.revision_2026_11_05';

    public const RETIRE = ['lal-kitab-report', 'lal-kitab-wisdom-pack', 'pitra-dosh-nivaran-puja', 'palmistry-photo-report'];

    /** Old slug => [new slug, new English title, new Hindi title]. */
    public const RENAME = [
        'santan-badha-puja' => ['santan-prapti-puja', 'Santan Prapti Puja', 'संतान प्राप्ति पूजा'],
        'shatru-badha-nazar-dosh-puja' => ['shatru-badha-puja', 'Shatru Badha Puja', 'शत्रु बाधा पूजा'],
        'nasha-mukti-prarthana' => ['nasha-mukti-puja', 'Nasha Mukti Puja', 'नशा मुक्ति पूजा'],
    ];

    /** Prices in rupees: slug => [variant-name fragment => price] (fragment '*' = every variant). */
    public const PRICES = [
        'premium-personalized-kundli' => ['*' => 2100],
        'couple-kundli-compatibility-report' => ['*' => 2100],
        'varshphal-yearly-report' => ['*' => 2100],
        'mahamrityunjay-puja' => ['Single' => 25000, 'Couple' => 31000, 'Family' => 41000],
        'maa-baglamukhi-puja' => ['Single' => 49000, 'Couple' => 61000, 'Family' => 81000],
        'kaal-bhairav-puja' => ['Single' => 49000, 'Couple' => 61000, 'Family' => 81000],
        'nasha-mukti-puja' => ['Single' => 5100, 'Couple' => 7100, 'Family' => 11000],
    ];

    public const VASTU_VARIANTS = [
        ['name' => 'Online review of floor plan', 'mode' => 'video', 'duration_min' => 45, 'price' => 5100],
        ['name' => 'On-site visit — Patna', 'mode' => 'onsite', 'duration_min' => 90, 'price' => 11000, 'was' => 'On-site visit (Patna)'],
        ['name' => 'On-site visit — outside Patna (in Bihar)', 'mode' => 'onsite', 'duration_min' => 120, 'price' => 15000],
        ['name' => 'On-site visit — outside Bihar', 'mode' => 'onsite', 'duration_min' => 180, 'price' => 25000],
    ];

    public const PALM_VARIANTS = [
        ['name' => 'Online — written report from palm photos', 'mode' => 'report', 'duration_min' => null, 'price' => 1100],
        ['name' => 'Video call — 30 min', 'mode' => 'video', 'duration_min' => 30, 'price' => 2500, 'was' => 'Video call'],
        ['name' => 'Audio call — 30 min', 'mode' => 'audio', 'duration_min' => 30, 'price' => 2100, 'was' => 'Audio call'],
        ['name' => 'In person (Patna) — 30 min', 'mode' => 'in_person', 'duration_min' => 30, 'price' => 2100, 'was' => 'In person at Patna office'],
    ];

    private const PUJA_PACKAGES = [
        ['name' => 'Single — sankalp for 1 person', 'mode' => 'puja', 'participants' => 1],
        ['name' => 'Couple — sankalp for 2 people', 'mode' => 'puja', 'participants' => 2],
        ['name' => 'Family — sankalp for up to 6 members', 'mode' => 'puja', 'participants' => 6],
    ];

    private const HOME_PUJA = [
        ['name' => 'At your home — Patna', 'mode' => 'puja', 'participants' => 6],
        ['name' => 'At your home — elsewhere in Bihar', 'mode' => 'puja', 'participants' => 6],
        ['name' => 'At your home — outside Bihar', 'mode' => 'puja', 'participants' => 6],
    ];

    /**
     * New services: slug => [family, title, Hindi title, summary, variants, [prices in variant order], motif].
     *
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: array<int, array<string, mixed>>, 5: array<int, int>, 6: string}>
     */
    public static function newServices(): array
    {
        return [
            'durga-saptashati-path' => ['puja', 'Durga Saptashati Path', 'दुर्गा सप्तशती पाठ',
                'All 700 verses of the Devi Mahatmya recited with kavach, argala and kilak, for protection, strength and removal of obstacles.',
                self::PUJA_PACKAGES, [11000, 15100, 21000], 'trishul'],
            'shrimad-bhagavad-gita-path' => ['puja', 'Shrimad Bhagavad Gita Path', 'श्रीमद्भगवद्गीता पाठ',
                'Recitation of all 18 chapters of the Bhagavad Gita with Gita Mahatmya and havan — for peace of mind, clarity and the peace of departed souls.',
                self::PUJA_PACKAGES, [5100, 7100, 11000], 'book'],
            'gand-mool-nakshatra-shanti-puja' => ['puja', 'Gand Mool Nakshatra Shanti Puja', 'गंड मूल नक्षत्र शांति पूजा',
                'Shanti puja for a child born in Ashwini, Ashlesha, Magha, Jyeshtha, Mool or Revati, traditionally done when the same nakshatra returns on about the 27th day.',
                [['name' => 'Online — performed by Acharya Ji\'s team', 'mode' => 'puja', 'participants' => 3], ['name' => 'At your home — Patna', 'mode' => 'puja', 'participants' => 6]],
                [11000, 15100], 'star'],
            'griha-pravesh-puja' => ['puja', 'Griha Pravesh Puja', 'गृह प्रवेश पूजा',
                'House-warming puja on a chosen muhurat: Ganesh puja, Vastu shanti, Navgrah puja, havan and the first entry with kalash.',
                self::HOME_PUJA, [11000, 15100, 21000], 'house'],
            'griha-aarambh-bhoomi-pujan' => ['puja', 'Griha Aarambh (Bhoomi Pujan)', 'गृह आरंभ (भूमि पूजन)',
                'Bhoomi pujan and foundation-stone puja before construction starts, on a muhurat checked for the plot and the owner.',
                self::HOME_PUJA, [11000, 15100, 21000], 'lotus'],
            'nyayik-uljhan-nivaran-puja' => ['puja', 'Nyayik Uljhan Nivaran Puja (Court Case)', 'न्यायिक उलझन निवारण पूजा',
                'Puja and jaap for people caught in court cases and legal disputes — Maa Baglamukhi, Hanuman and Shani shanti as your chart indicates.',
                self::PUJA_PACKAGES, [11000, 15100, 21000], 'shield'],
            'tarot-reading' => ['consultation', 'Tarot Reading', 'टैरो रीडिंग',
                'A live tarot card reading on love, career, money or any question — by video, audio or chat, from anywhere.',
                [['name' => 'Video call — 30 min', 'mode' => 'video', 'duration_min' => 30], ['name' => 'Audio call — 30 min', 'mode' => 'audio', 'duration_min' => 30], ['name' => 'Chat — 30 min', 'mode' => 'chat', 'duration_min' => 30]],
                [1100, 900, 700], 'eye'],
        ];
    }

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        DB::transaction(function () {
            $this->retire();
            $this->rename();
            $this->addNewServices();
            $this->palmistry();
            $this->vastu();
            $this->prices();
            $this->kaalSarpOnSale();
            $this->cleanText();
        });
        $settings->set(self::FLAG, now()->toDateString());
    }

    private function retire(): void
    {
        DB::table('offerings')->whereIn('slug', self::RETIRE)->update(['status' => 'retired', 'featured' => false, 'updated_at' => now()]);
    }

    private function rename(): void
    {
        foreach (self::RENAME as $old => [$new, $title, $titleHi]) {
            $o = Offering::where('slug', $old)->first();
            if (! $o || Offering::where('slug', $new)->exists()) {
                continue;
            }
            $o->forceFill(['slug' => $new])->save();
            $o->translations()->where('locale', 'en')->update(['title' => $title]);
            $o->translations()->where('locale', 'hi')->update(['title' => $titleHi]);
            if ($o->image_path === "images/art/$old.jpg") {
                $o->forceFill(['image_path' => "images/art/$new.jpg"])->save();
            }
        }
        // An owner-made "Shatru Nash" service takes the new name as well.
        DB::table('offering_translations')->where('locale', 'en')->where('title', 'like', '%Shatru Nash%')
            ->update(['title' => DB::raw("REPLACE(title, 'Shatru Nash', 'Shatru Badha')")]);
    }

    private function addNewServices(): void
    {
        $fam = ServiceFamily::pluck('id', 'code');
        $supplier = DB::table('suppliers')->where('authorization_doc', 'in-house')->value('id');
        $sort = 60;
        foreach (self::newServices() as $slug => [$family, $title, $titleHi, $summary, $variants, $prices]) {
            if (Offering::where('slug', $slug)->exists() || ! isset($fam[$family])) {
                continue;
            }
            $o = Offering::create(['family_id' => $fam[$family], 'slug' => $slug, 'status' => 'active', 'featured' => false, 'sort' => $sort++,
                'image_path' => is_file(public_path("images/art/$slug.jpg")) ? "images/art/$slug.jpg" : null]);
            $o->forceFill(['activated_at' => now()])->save();
            $o->translations()->create(['locale' => 'en', 'title' => $title, 'summary' => $summary, 'meta_description' => $summary]);
            foreach ($variants as $i => $v) {
                $o->variants()->create($v + ['sort' => $i, 'participants' => $v['participants'] ?? 1, 'price_minor' => $prices[$i] * 100]);
            }
            if ($family === 'puja' && $supplier) {
                DB::table('offering_supplier')->insertOrIgnore(['offering_id' => $o->id, 'supplier_id' => $supplier]);
            }
        }
    }

    /** One palmistry service: photo report online, video/audio call, or in person in Patna. */
    private function palmistry(): void
    {
        $this->syncVariants('palmistry', self::PALM_VARIANTS);
        Offering::where('slug', 'palmistry')->update(['featured' => true]);
        DB::table('offering_translations')->where('offering_id', Offering::where('slug', 'palmistry')->value('id'))->where('locale', 'en')
            ->where('title', 'Palmistry (Hastrekha)')->update(['title' => 'Palmistry (Hastrekha) — online or in person']);
    }

    private function vastu(): void
    {
        $this->syncVariants('vastu-consultation', self::VASTU_VARIANTS);
    }

    /**
     * Brings an offering's variants to the given list: renames old variants, sets prices,
     * adds missing ones and hides variants that are no longer offered.
     *
     * @param  array<int, array{name: string, mode: string, duration_min: ?int, price: int, was?: string}>  $list
     */
    private function syncVariants(string $slug, array $list): void
    {
        $o = Offering::where('slug', $slug)->first();
        if (! $o) {
            return;
        }
        $keep = [];
        foreach ($list as $i => $v) {
            $existing = $o->variants()->where('name', $v['name'])->first()
                ?? (isset($v['was']) ? $o->variants()->where('name', $v['was'])->first() : null);
            $data = ['name' => $v['name'], 'mode' => $v['mode'], 'duration_min' => $v['duration_min'], 'price_minor' => $v['price'] * 100, 'active' => true, 'sort' => $i];
            if ($existing) {
                $existing->forceFill($data)->save();
                $keep[] = $existing->id;
            } else {
                $keep[] = $o->variants()->create($data + ['participants' => 1])->id;
            }
        }
        $o->variants()->whereNotIn('id', $keep)->update(['active' => false]);
    }

    private function prices(): void
    {
        foreach (self::PRICES as $slug => $rule) {
            $o = Offering::where('slug', $slug)->first();
            foreach ($o?->variants ?? [] as $v) {
                foreach ($rule as $fragment => $rupees) {
                    if ($fragment === '*' || str_starts_with($v->name, $fragment)) {
                        $v->forceFill(['price_minor' => $rupees * 100])->save();
                        break;
                    }
                }
            }
        }
    }

    private function kaalSarpOnSale(): void
    {
        DB::table('offerings')->where('slug', 'kaal-sarp-dosh-nivaran-puja')->whereIn('status', ['draft', 'paused'])
            ->update(['status' => 'active', 'activated_at' => now(), 'updated_at' => now()]);
        DB::table('offerings')->where('slug', 'kaal-sarp-dosh-nivaran-puja')->update(['featured' => true]);
    }

    /** Removes mentions of retired services from seeded text and links. */
    private function cleanText(): void
    {
        $puja = Offering::where('slug', 'puja-rituals')->first()?->translations()->where('locale', 'en')->first();
        if ($puja && is_array($puja->includes)) {
            $puja->includes = array_map(fn ($i) => $i === 'Kaal Sarp and Pitra Dosh nivaran' ? 'Kaal Sarp Dosh nivaran and Gand Mool shanti' : $i, $puja->includes);
            $puja->save();
        }
        foreach (DB::table('posts')->whereNotNull('services')->get(['id', 'services']) as $p) {
            $list = json_decode((string) $p->services, true) ?: [];
            $clean = array_values(array_map(fn ($s) => self::RENAME[$s][0] ?? $s, array_diff($list, self::RETIRE)));
            if ($clean !== $list) {
                DB::table('posts')->where('id', $p->id)->update(['services' => json_encode($clean)]);
            }
        }
        DB::table('bundle_items')->whereIn('item_offering_id', Offering::whereIn('slug', self::RETIRE)->pluck('id'))->delete();
    }
}
