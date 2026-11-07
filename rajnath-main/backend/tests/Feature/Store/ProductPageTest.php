<?php

namespace Tests\Feature\Store;

use App\Support\Settings\SettingsRepository;
use Database\Seeders\StoreImagesSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Real product photos and the full e-commerce product page (web + app API). */
class ProductPageTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

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

    private function payload(object $p, array $extra = []): array
    {
        return ['name' => $p->name, 'category' => $p->category, 'summary' => $p->summary, 'price' => (string) ($p->price_minor / 100), 'stock' => 5, 'max_per_order' => 1, 'active' => '1'] + $extra;
    }

    public function test_owner_edits_page_details_and_can_go_back_to_built_in_text(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
        $owner = $this->makeStaff(['owner']);
        $p = DB::table('products')->where('slug', 'ruby-manik')->first();
        $this->asStaff($owner)->get(route('admin.store.edit', $p->ref))->assertOk()->assertSee('Product page details')->assertSee('Enhance with AI')->assertSee('Ring finger, right hand');

        $this->asStaff($owner)->post(route('admin.store.update', $p->ref), $this->payload($p, [
            'highlights' => "Hand-picked by Acharya Ji\n- Unheated",
            'sections' => [['title' => 'Benefits', 'kind' => 'list', 'items' => "Leadership\nसम्मान"], ['title' => 'How to wear', 'kind' => 'table', 'items' => 'Finger: Ring finger'], ['title' => '', 'items' => 'dropped']],
            'faqs' => "Q: Is it heated?\nA: No, it is unheated.",
        ]))->assertSessionHasNoErrors();
        $this->get(route('store.show', 'ruby-manik'))->assertOk()->assertSee('Hand-picked by Acharya Ji')->assertSee('सम्मान')->assertSee('Is it heated?')->assertDontSee('Who should wear it');
        $g = $this->getJson('/api/v1/store/products/ruby-manik')->json('data.guide');
        $this->assertSame(['Hand-picked by Acharya Ji', 'Unheated'], $g['highlights']);
        $this->assertSame([['Finger', 'Ring finger']], $g['sections'][1]['items']);

        $this->asStaff($owner)->post(route('admin.store.update', $p->ref), $this->payload($p, ['details_reset' => '1']))->assertSessionHasNoErrors();
        $this->get(route('store.show', 'ruby-manik'))->assertSee('Who should wear it');
    }

    public function test_ai_writes_a_draft_into_the_form_without_saving(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
        $s = app(SettingsRepository::class);
        foreach (['ai.enabled' => true, 'ai.order' => 'groq', 'ai.groq.enabled' => true] as $k => $v) {
            $s->set($k, $v);
        }
        $s->set('ai.groq.key', 'k', secret: true);
        $sent = [];
        Http::fake(['api.groq.com/*' => function ($req) use (&$sent) {
            $sent[] = $req->data();

            return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode([
                'summary' => 'Natural ruby for a strong Sun.', 'description' => 'Para one.\n\nPara two.', 'highlights' => ['Certified', 'Energised'],
                'sections' => [['title' => 'Benefits', 'kind' => 'list', 'items' => ['Confidence']], ['title' => 'How to wear', 'kind' => 'table', 'items' => [['Metal', 'Gold']]]],
                'faqs' => [['q' => 'Who can wear ruby?', 'a' => 'After a chart check.']],
            ])]]]]);
        }]);
        $owner = $this->makeStaff(['owner']);
        $p = DB::table('products')->where('slug', 'ruby-manik')->first();
        $this->asStaff($owner)->post(route('admin.store.ai', $p->ref), ['instructions' => 'Keep it short'])->assertRedirect(route('admin.store.edit', $p->ref))
            ->assertSessionHasInput('summary', 'Natural ruby for a strong Sun.')->assertSessionHasInput('faqs', "Q: Who can wear ruby?\nA: After a chart check.");
        $this->assertStringContainsString('Keep it short', $sent[0]['messages'][1]['content']);
        $this->assertNull(DB::table('products')->where('id', $p->id)->value('details'), 'draft is not saved');

        $s->set('ai.enabled', false);
        $this->asStaff($owner)->post(route('admin.store.ai', $p->ref))->assertSessionHasErrors('ai');
    }
}
