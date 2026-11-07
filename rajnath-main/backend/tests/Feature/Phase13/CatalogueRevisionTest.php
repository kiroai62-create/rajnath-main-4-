<?php

namespace Tests\Feature\Phase13;

use App\Domain\Palmistry\PalmReadings;
use App\Models\Offering;
use Database\Seeders\CatalogueRevisionSeeder;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Database\Seeders\PujaActivationSeeder;
use Database\Seeders\RichContentSeeder;
use Database\Seeders\ServiceContentSeeder;
use Database\Seeders\StoreLaunchSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Owner's catalogue changes of 5 Nov 2026 and the detailed service pages. */
class CatalogueRevisionTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogue(): void
    {
        foreach ([CatalogueSeeder::class, MarketPriceSeeder::class, ServiceContentSeeder::class, PujaActivationSeeder::class, CatalogueRevisionSeeder::class, RichContentSeeder::class] as $s) {
            $this->seed($s);
        }
    }

    private function price(string $slug, string $variant): int
    {
        return (int) Offering::where('slug', $slug)->first()->variants()->where('name', 'like', "$variant%")->where('active', true)->value('price_minor') / 100;
    }

    public function test_services_are_retired_renamed_added_and_repriced(): void
    {
        $this->seedCatalogue();

        foreach (['lal-kitab-report', 'lal-kitab-wisdom-pack', 'pitra-dosh-nivaran-puja', 'palmistry-photo-report'] as $slug) {
            $this->assertSame('retired', Offering::where('slug', $slug)->value('status'), $slug);
        }
        $this->get('/services/lal-kitab-report')->assertNotFound();
        $this->get('/services/santan-badha-puja')->assertRedirect('/services/santan-prapti-puja');
        $this->get('/services/palmistry-photo-report')->assertRedirect('/services/palmistry');
        $this->get('/services/santan-prapti-puja')->assertOk()->assertSee('Santan Prapti Puja');
        $this->get('/services/shatru-badha-puja')->assertOk()->assertSee('Shatru Badha Puja');
        $this->get('/services/nasha-mukti-puja')->assertOk()->assertSee('Nasha Mukti Puja');

        foreach (array_keys(CatalogueRevisionSeeder::newServices()) as $slug) {
            $this->get("/services/$slug")->assertOk()->assertSee('How it is done')->assertSee('How booking works');
        }
        $this->assertSame('active', Offering::where('slug', 'kaal-sarp-dosh-nivaran-puja')->value('status'));

        $this->assertSame(11000, $this->price('vastu-consultation', 'On-site visit — Patna'));
        $this->assertSame(15000, $this->price('vastu-consultation', 'On-site visit — outside Patna'));
        $this->assertSame(25000, $this->price('vastu-consultation', 'On-site visit — outside Bihar'));
        foreach (['premium-personalized-kundli', 'couple-kundli-compatibility-report', 'varshphal-yearly-report'] as $slug) {
            $this->assertSame(2100, $this->price($slug, ''), $slug);
        }
        $this->assertSame(25000, $this->price('mahamrityunjay-puja', 'Single'));
        $this->assertSame(49000, $this->price('maa-baglamukhi-puja', 'Single'));
        $this->assertSame(49000, $this->price('kaal-bhairav-puja', 'Single'));
        $this->assertSame(1100, $this->price('palmistry', 'Online'));

        // every public service now has a step-by-step procedure, benefits and FAQs
        $thin = Offering::public()->with('translations')->get()->filter(fn ($o) => empty($o->t('en')->process) || empty($o->t('en')->faqs));
        $this->assertSame([], $thin->pluck('slug')->all());
    }

    public function test_runs_once_and_keeps_owner_changes(): void
    {
        $this->seedCatalogue();
        Offering::where('slug', 'premium-personalized-kundli')->first()->variants()->update(['price_minor' => 250000]);
        Offering::where('slug', 'lal-kitab-report')->update(['status' => 'active']);
        $this->seed(CatalogueRevisionSeeder::class);
        $this->assertSame(2500, $this->price('premium-personalized-kundli', ''));
        $this->assertSame('active', Offering::where('slug', 'lal-kitab-report')->value('status'));
    }

    public function test_owner_text_is_not_replaced_by_the_detailed_content(): void
    {
        foreach ([CatalogueSeeder::class, MarketPriceSeeder::class, ServiceContentSeeder::class] as $s) {
            $this->seed($s);
        }
        DB::table('offering_translations')->where('offering_id', Offering::where('slug', 'numerology')->value('id'))->where('locale', 'en')->update(['body' => 'Owner text']);
        $this->seed(RichContentSeeder::class);
        $t = Offering::where('slug', 'numerology')->first()->t('en');
        $this->assertSame('Owner text', $t->body);
        $this->assertNotEmpty($t->process, 'empty procedure is still filled');
        $this->assertStringContainsString('Chaldean', Offering::where('slug', 'business-astrology')->first()->t('en')->body.' '.Offering::where('slug', 'numerology')->first()->t('en')->process[1]);
    }

    public function test_store_opens_with_the_full_list_once_and_keeps_owner_products(): void
    {
        $this->seed(StoreSeeder::class);
        DB::table('products')->where('slug', 'ruby-manik')->update(['price_minor' => 1500000, 'updated_at' => now()->addMinute()]);
        $this->seed(StoreLaunchSeeder::class);
        $this->assertSame(count(StoreSeeder::ITEMS) - 1, DB::table('products')->where('active', true)->where('stock', '>', 0)->count());
        $this->assertSame(0, (int) DB::table('products')->where('slug', 'ruby-manik')->value('active'), 'owner-edited product untouched');
        $this->assertSame('Ceylon (Sri Lanka)', DB::table('products')->where('slug', 'yellow-sapphire-pukhraj')->value('origin'));
        $this->get('/gems')->assertOk()->assertSee('Fourteen Mukhi Rudraksha')->assertSee('Parad Shivling');
        $this->get('/gems/griha-pravesh-puja-kit')->assertOk()->assertSee('₹2,100');
    }

    public function test_online_palm_option_creates_a_photo_upload_step(): void
    {
        $snap = fn ($slug, $mode) => ['offering' => ['slug' => $slug], 'variant' => ['mode' => $mode]];
        $this->assertTrue(PalmReadings::isPalmOrder($snap('palmistry', 'report')));
        $this->assertTrue(PalmReadings::isPalmOrder($snap('palmistry-photo-report', 'report')));
        $this->assertFalse(PalmReadings::isPalmOrder($snap('palmistry', 'video')));
        $this->assertFalse(PalmReadings::isPalmOrder($snap('career-report', 'report')));
    }
}
