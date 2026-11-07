<?php

namespace Tests\Feature\Phase13;

use App\Domain\Assistant\AssistantTools;
use App\Support\Settings\SettingsRepository;
use Database\Seeders\BlogSeeder;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\LegalSeeder;
use Database\Seeders\ShowcaseSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The assistant can look up every part of the website. */
class AssistantSiteKnowledgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([CatalogueSeeder::class, StoreSeeder::class, ShowcaseSeeder::class, LegalSeeder::class, BlogSeeder::class] as $s) {
            $this->seed($s);
        }
        DB::table('products')->update(['active' => true, 'stock' => 3]);
        DB::table('places')->insert(['id' => 1260086, 'name' => 'Patna', 'ascii_name' => 'Patna', 'admin1' => 'Bihar', 'country_code' => 'IN', 'country' => 'India', 'lat' => 25.59408, 'lng' => 85.13563, 'tz_name' => 'Asia/Kolkata', 'population' => 1]);
    }

    public function test_tools_cover_products_articles_profile_policies_festivals_and_calculators(): void
    {
        $t = new AssistantTools(null, 'web');
        $p = $t->run('product_details', ['slug' => 'yellow-sapphire-pukhraj']);
        $this->assertSame('Index finger, right hand', collect($p['sections'])->firstWhere('title', 'How to wear')['items'][2][1]);
        $this->assertStringContainsString('Rajnath', json_encode($t->run('about_acharya', [])));
        $this->assertNotEmpty($t->run('policy', ['name' => 'privacy'])['text']);
        $slug = DB::table('posts')->where('status', 'published')->value('slug');
        $this->assertNotEmpty($t->run('read_article', ['slug' => $slug])['text']);
        $this->assertNotEmpty($t->run('festivals', ['year' => 2026])['data']);
        $this->assertNotEmpty($t->run('planet_transits', [])['data']);
        $this->assertArrayHasKey('result', $t->run('run_calculator', ['slug' => 'numerology', 'name' => 'Asha Kumari', 'birth_date' => '1990-05-14']));
        $this->assertArrayHasKey('result', $t->run('run_calculator', ['slug' => 'moon-sign', 'birth_date' => '1990-05-14', 'birth_time' => '06:30', 'birth_place' => 'Patna']));
        $this->assertArrayHasKey('problems', $t->run('run_calculator', ['slug' => 'kundli', 'birth_date' => '1990-05-14']));
        $this->assertArrayHasKey('error', $t->run('muhurat', ['event' => 'nonsense']));
    }

    public function test_chat_knows_the_page_and_the_site(): void
    {
        $s = app(SettingsRepository::class);
        foreach (['ai.enabled' => true, 'ai.order' => 'groq', 'ai.groq.enabled' => true] as $k => $v) {
            $s->set($k, $v);
        }
        $s->set('ai.groq.key', 'k', secret: true);
        $sent = [];
        Http::fake(['api.groq.com/*' => function ($req) use (&$sent) {
            $sent[] = $req->data();

            return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'Ji haan.']]]]);
        }]);
        $this->postJson('/api/v1/assistant/chat', ['message' => 'Ye mere liye sahi hai?', 'page' => 'product:ruby-manik'])->assertOk();
        $system = $sent[0]['messages'][0]['content'];
        $this->assertStringContainsString('product:ruby-manik', $system);
        $this->assertStringContainsString('product_details', $system);
        $this->assertStringContainsString('/gems', $system);
        $this->assertContains('run_calculator', array_column(array_column($sent[0]['tools'], 'function'), 'name'));
        $this->postJson('/api/v1/assistant/chat', ['message' => 'hello there', 'page' => '<script>'])->assertOk();
        $this->assertStringNotContainsString('CURRENT PAGE', $sent[1]['messages'][0]['content']);
        $this->get(route('store.show', 'ruby-manik'))->assertSee('name="arj-page" content="product:ruby-manik"', false)->assertSee('Ask AI about this');
    }
}
