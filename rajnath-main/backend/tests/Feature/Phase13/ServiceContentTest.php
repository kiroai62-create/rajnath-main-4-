<?php

namespace Tests\Feature\Phase13;

use App\Models\Offering;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Database\Seeders\PujaActivationSeeder;
use Database\Seeders\ServiceContentSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Full text and an own illustration for every catalogue service; owner edits are kept. */
class ServiceContentTest extends TestCase
{
    use RefreshDatabase, \Tests\Concerns\StaffHelpers;

    public function test_placeholder_text_is_replaced_and_every_service_has_its_own_image(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        // the owner changed one summary and one photo before the content seed ran
        $career = Offering::where('slug', 'career-report')->first();
        $career->translations()->where('locale', 'en')->update(['summary' => 'Owner summary']);
        DB::table('offerings')->where('slug', 'numerology')->update(['image_path' => 'uploads/services/mine.jpg']);

        $this->seed(ServiceContentSeeder::class);

        $this->assertSame(0, DB::table('offering_translations')->where('summary', 'like', '%details to be finalised%')->count());
        $this->assertSame('Owner summary', $career->t('en')->summary);
        $this->assertSame('uploads/services/mine.jpg', Offering::where('slug', 'numerology')->value('image_path'));
        $this->assertSame('images/owner/portrait-navy-480.jpg', Offering::where('slug', 'consultation-with-acharya-ji')->value('image_path'));
        $art = DB::table('offerings')->where('image_path', 'like', 'images/art/%')->pluck('image_path');
        $this->assertGreaterThan(80, $art->count());
        $this->assertSame($art->count(), $art->unique()->count());
        foreach ($art as $p) {
            $this->assertFileExists(public_path($p));
        }

        $this->get('/services/sade-sati-report')->assertOk()->assertSee('images/art/sade-sati-report', false)->assertDontSee('details to be finalised');
        $this->get('/services/sade-sati-report?lang=hi')->assertOk()->assertSee('साढ़े');
        $this->get('/courses?lang=en')->assertOk()->assertSee('Coming soon');
    }

    public function test_store_starter_list_is_hidden_until_the_owner_adds_real_stock(): void
    {
        $this->seed(StoreSeeder::class);
        $this->assertSame(count(StoreSeeder::ITEMS), DB::table('products')->where('active', false)->where('stock', 0)->count());
        $this->assertSame(0, DB::table('products')->whereNull('image_path')->count());
        $this->get('/gems')->assertOk()->assertDontSee('Yellow Sapphire (Pukhraj)');
    }

    public function test_pujas_and_chadhavas_go_live_with_the_in_house_priest_once(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        $this->seed(ServiceContentSeeder::class);
        $this->seed(PujaActivationSeeder::class);
        $live = fn (string $code) => DB::table('offerings as o')->join('service_families as f', 'f.id', '=', 'o.family_id')->where('f.code', $code)->where('o.status', 'active')->count();
        $this->assertSame(46, $live('puja'));
        $this->assertSame(7, $live('chadhava'));
        $this->assertSame(1, DB::table('suppliers')->where('authorization_doc', 'in-house')->count());
        // still not sold on the website: pujas are arranged through the request form
        $this->get('/services/rudrabhishek')->assertRedirect(route('puja-request.create', ['puja' => 'Rudrabhishek']));
        $this->get('/services?family=chadhava')->assertRedirect(route('puja-request.create'));

        // the owner switches one off; a later deploy leaves it off
        DB::table('offerings')->where('slug', 'rudrabhishek')->update(['status' => 'draft']);
        $this->seed(PujaActivationSeeder::class);
        $this->assertSame('draft', DB::table('offerings')->where('slug', 'rudrabhishek')->value('status'));
    }

    public function test_site_is_english_only_by_default(): void
    {
        config(['locales.enabled' => ['en']]);
        $this->seed(CatalogueSeeder::class);
        $this->get('/services?lang=hi')->assertOk()->assertSee('<html lang="en">', false)->assertDontSee('hreflang="hi"', false)->assertDontSee('हिन्दी');
        $this->getJson('/api/v1/meta')->assertJsonPath('locales', ['en']);
        DB::table('horoscopes')->insert(['kind' => 'rashi', 'topic' => 'general', 'period' => 'daily', 'sign' => 0, 'starts_on' => now('Asia/Kolkata')->toDateString(),
            'ends_on' => now('Asia/Kolkata')->toDateString(), 'locale' => 'en', 'body' => 'Aries: a calm day for planning.', 'status' => 'published', 'author_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/rashifal')->assertOk()->assertSee('Aries: a calm day for planning.')->assertDontSee('हिन्दी');
        $this->getJson('/api/v1/horoscopes')->assertOk()->assertJsonPath('meta.lang', 'en')->assertJsonPath('data.0.body', 'Aries: a calm day for planning.');
        // admin hides the translation fields
        $this->syncRbac();
        $this->asStaff($this->makeStaff(['owner']));
        $this->get(route('admin.catalogue.edit', Offering::where('slug', 'numerology')->value('ref')))->assertOk()->assertDontSee('Other languages');
        $this->get(route('admin.profile.edit'))->assertOk()->assertDontSee('Hindi (optional)');
        $this->get(route('admin.legal.index'))->assertOk()->assertDontSee('Uses English');
    }
}
