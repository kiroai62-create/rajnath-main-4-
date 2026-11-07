<?php

namespace Tests\Feature\Phase13;

use App\Domain\Astro\Engine;
use App\Domain\Content\GocharRashifal;
use App\Domain\Content\RashifalWriter;
use App\Models\Offering;
use App\Models\User;
use App\Support\Settings\SettingsRepository;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogueRevisionSeeder;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The assistant can look things up and start a booking; Rashifal is written from real transits. */
class AiAgentAndRashifalTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array> queued AI replies (each is a choices.0.message) */
    private array $replies = [];

    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();
        $s = app(SettingsRepository::class);
        $s->set('ai.enabled', true);
        $s->set('ai.order', 'groq');
        $s->set('ai.groq.enabled', true);
        $s->set('ai.groq.key', 'gsk_test', secret: true);
        $s->set('payments.razorpay.key_id', 'rzp_test_'.str_repeat('A', 14));
        $s->set('payments.razorpay.key_secret', 'sec', secret: true);
        Http::fake([
            'api.groq.com/*' => function ($req) {
                $this->requests[] = $req->data();
                $msg = array_shift($this->replies) ?? ['role' => 'assistant', 'content' => 'OK'];

                return Http::response(['choices' => [['message' => $msg]], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5]]);
            },
            'api.razorpay.com/v1/orders' => Http::response(['id' => 'order_AI1', 'amount' => 110000]),
        ]);
    }

    public function test_signed_in_customer_is_sent_to_the_details_form_by_the_assistant(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        $this->seed(CatalogueRevisionSeeder::class);
        $v = Offering::where('slug', 'palmistry')->first()->variants()->where('mode', 'report')->first();
        $this->replies = [
            ['role' => 'assistant', 'content' => null, 'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => 'service_details', 'arguments' => '{"slug":"palmistry"}']]]],
            ['role' => 'assistant', 'content' => null, 'tool_calls' => [['id' => 'c2', 'type' => 'function', 'function' => ['name' => 'start_order', 'arguments' => json_encode(['option_ref' => $v->ref])]]]],
            ['role' => 'assistant', 'content' => 'Done. Tap Pay to finish, then upload your palm photos.'],
        ];
        $u = new User(['name' => 'Asha', 'email' => 'asha@example.com']);
        $u->save();

        $r = $this->actingAs($u)->postJson('/assistant/chat', ['message' => 'Mujhe online palm reading chahiye, book kar do'])->assertOk()->json();

        $this->assertSame('Done. Tap Pay to finish, then upload your palm photos.', $r['reply']);
        // details come first: the assistant hands over the details form, no unpaid order is created
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertContains(route('checkout.details', $v->ref), array_column($r['actions'], 'url'));
        $this->assertContains('details', array_column($r['actions'], 'type'));
        // tools were offered, and the tool result reached the model
        $this->assertContains('start_order', array_column(array_column($this->requests[0]['tools'], 'function'), 'name'));
        $this->assertSame('tool', end($this->requests[2]['messages'])['role']);
    }

    public function test_visitors_get_no_booking_tools_and_cannot_order(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->replies = [['role' => 'assistant', 'content' => 'Please sign in first.']];
        $this->postJson('/assistant/chat', ['message' => 'Book a puja'])->assertOk();
        $names = array_column(array_column($this->requests[0]['tools'], 'function'), 'name');
        $this->assertContains('search_services', $names);
        $this->assertNotContains('start_order', $names);
        $this->assertSame(0, DB::table('orders')->count());
    }

    public function test_rashifal_is_written_from_transits_once_and_staff_text_is_kept(): void
    {
        if (! app(Engine::class)->available()) {
            $this->markTestSkipped('Ephemeris engine not built.');
        }
        $start = CarbonImmutable::parse('2026-11-06');
        DB::table('horoscopes')->insert(['kind' => 'rashi', 'topic' => 'general', 'period' => 'daily', 'sign' => 0, 'starts_on' => '2026-11-06', 'ends_on' => '2026-11-06',
            'locale' => 'en', 'body' => 'Written by our staff for Mesha.', 'status' => 'published', 'source' => 'staff', 'author_id' => 1, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $json = json_encode(collect(range(1, 11))->mapWithKeys(fn ($i) => [$i => "Sign $i: a calm and productive day as the Moon moves through a friendly house. Remedy: offer water to the Sun."])->all());
        $this->replies = [['role' => 'assistant', 'content' => "```json\n$json\n```"]];

        $n = app(RashifalWriter::class)->write('rashi', 'general', 'daily', $start, 'en');

        $this->assertSame(11, $n);
        $this->assertSame('Written by our staff for Mesha.', DB::table('horoscopes')->where('sign', 0)->value('body'));
        $this->assertSame(11, DB::table('horoscopes')->where('source', 'ai')->where('status', 'published')->count());
        $prompt = $this->requests[0]['messages'][1]['content'];
        $this->assertStringContainsString('Saturn:', $prompt);
        $this->assertStringContainsString('1 = Vrishabha', $prompt);
        $this->assertSame(0, app(RashifalWriter::class)->write('rashi', 'general', 'daily', $start, 'en'), 'nothing left to write');
        $this->get('/rashifal?period=daily')->assertOk();
    }

    public function test_rashifal_drafts_when_autopublish_is_off(): void
    {
        if (! app(Engine::class)->available()) {
            $this->markTestSkipped('Ephemeris engine not built.');
        }
        app(SettingsRepository::class)->set('ai.rashifal.autopublish', false);
        $this->replies = [['role' => 'assistant', 'content' => json_encode(collect(range(1, 9))->mapWithKeys(fn ($i) => [$i - 1 => "Mulank $i: this month favours steady work and patient talks with family."])->all())]];
        $this->assertSame(9, app(RashifalWriter::class)->write('numerology', 'general', 'monthly', CarbonImmutable::parse('2026-11-01'), 'en'));
        $this->assertSame(9, DB::table('horoscopes')->where('status', 'draft')->where('source', 'ai')->count());
    }

    public function test_engine_writes_rashifal_without_a_key_and_ai_replaces_it(): void
    {
        if (! app(Engine::class)->available()) {
            $this->markTestSkipped('Ephemeris engine not built.');
        }
        $day = CarbonImmutable::parse('2026-11-06', 'Asia/Kolkata');
        DB::table('horoscopes')->insert(['kind' => 'rashi', 'topic' => 'general', 'period' => 'daily', 'sign' => 4, 'starts_on' => '2026-11-06', 'ends_on' => '2026-11-06', 'locale' => 'en',
            'body' => 'Staff text for Simha.', 'status' => 'published', 'source' => 'staff', 'author_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $n = app(GocharRashifal::class)->ensureCurrent($day);
        // rashi (each language): 2 days × 12 + 6 topics × 12 (week) + 6 × 12 (month) + 12 + 12 = 192;
        // English only: nakshatra 2 × 27, numerology 9, tarot 24, Chinese 12 = 99; minus the staff row
        $languages = count(array_intersect(['en', 'hi'], config('locales.enabled')));
        $this->assertSame(192 * $languages + 99 - 1, $n);
        $mesha = DB::table('horoscopes')->where(['kind' => 'rashi', 'period' => 'daily', 'topic' => 'general', 'sign' => 0, 'starts_on' => '2026-11-06'])->first();
        $this->assertSame('engine', $mesha->source);
        $this->assertSame('published', $mesha->status);
        $this->assertStringContainsString('Remedy:', $mesha->body);
        $this->assertSame('Staff text for Simha.', DB::table('horoscopes')->where(['kind' => 'rashi', 'period' => 'daily', 'sign' => 4, 'starts_on' => '2026-11-06'])->value('body'));
        $this->assertSame(0, app(GocharRashifal::class)->ensureCurrent($day), 'nothing is written twice');

        // With a key the AI writer treats engine readings as missing and replaces them; staff text stays.
        $json = json_encode(collect(range(0, 11))->mapWithKeys(fn ($i) => [$i => "AI reading for sign $i: a steady day with help from friends. Remedy: offer water to the Sun."])->all());
        $this->replies = [['role' => 'assistant', 'content' => $json]];
        $this->assertSame(11, app(RashifalWriter::class)->write('rashi', 'general', 'daily', $day, 'en'));
        $rows = DB::table('horoscopes')->where(['kind' => 'rashi', 'period' => 'daily', 'topic' => 'general', 'starts_on' => '2026-11-06', 'locale' => 'en'])->get();
        $this->assertCount(12, $rows);
        $this->assertSame(['ai' => 11, 'staff' => 1], $rows->countBy('source')->sortKeys()->all());
    }
}
