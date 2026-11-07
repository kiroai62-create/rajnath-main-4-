<?php

namespace Tests\Feature\Store;

use Database\Seeders\StoreImagesSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Real product photos and the full e-commerce product page (web + app API). */
class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StoreSeeder::class);
        DB::table('products')->update(['active' => true, 'stock' => 5]);
    }

    public function test_placeholder_art_is_swapped_for_real_photos_but_owner_uploads_are_kept(): void
    {
        DB::table('products')->update(['image_path' => DB::raw("CONCAT('images/art/store-', slug, '.jpg')")]);
        DB::table('products')->where('slug', 'pearl-moti')->update(['image_path' => 'uploads/products/own.jpg']);
        $this->seed(StoreImagesSeeder::class);

        $this->assertSame('images/store/ruby-manik.jpg', DB::table('products')->where('slug', 'ruby-manik')->value('image_path'));
        $this->assertSame('uploads/products/own.jpg', DB::table('products')->where('slug', 'pearl-moti')->value('image_path'));
        $this->assertFileExists(public_path('images/store/ruby-manik.jpg'));
        $this->assertSame('images/art/store-shani-yantra.jpg', DB::table('products')->where('slug', 'shani-yantra')->value('image_path'), 'no bundled photo: placeholder stays');
    }

    public function test_web_product_page_has_benefits_how_to_wear_cautions_and_credit(): void
    {
        $this->seed(StoreImagesSeeder::class);
        $this->get(route('store.show', 'blue-sapphire-neelam'))->assertOk()
            ->assertSee('Benefits')->assertSee('How to wear')->assertSee('Middle finger, right hand')
            ->assertSee('Possible side effects and cautions')->assertSee('three days')->assertSee('Specifications')->assertSee('Questions')
            ->assertSee('Wikimedia Commons');
        $this->get(route('store.show', 'five-mukhi-rudraksha'))->assertOk()->assertSee('Kalagni Rudra')->assertSee('Cautions');
        $this->get(route('store.index'))->assertOk()->assertSee('Gemstones &amp; <span', false);
    }

    public function test_app_api_sends_the_guide_and_a_thumbnail(): void
    {
        $this->seed(StoreImagesSeeder::class);
        $d = $this->getJson('/api/v1/store/products/ruby-manik')->assertOk()->json('data');
        $this->assertSame('Gemstone', $d['guide']['category_label']);
        $this->assertSame(['Benefits', 'Who should wear it', 'How to wear', 'Possible side effects and cautions', 'Care'], array_column($d['guide']['sections'], 'title'));
        $this->assertNotEmpty($d['guide']['faqs']);
        $this->assertStringEndsWith('images/store/ruby-manik-thumb.jpg', $d['thumb_url']);
        $this->assertNotEmpty($d['guide']['credit']['source']);
        $list = $this->getJson('/api/v1/store/products')->assertOk()->json('data');
        $this->assertGreaterThan(50, count($list));
        $this->assertArrayNotHasKey('guide', $list[0]);
    }
}
