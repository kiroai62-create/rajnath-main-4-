<?php

namespace Tests\Feature\Phase13;

use App\Domain\Astro\Engine;
use App\Domain\Payments\Checkout;
use App\Domain\Payments\ServiceDetails;
use App\Domain\Puja\PujaBookings;
use App\Models\BirthProfile;
use App\Models\Offering;
use App\Models\User;
use App\Support\Settings\SettingsRepository;
use Database\Seeders\CatalogueRevisionSeeder;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Database\Seeders\PujaActivationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Owner decision 5 Nov 2026: every detail is collected first, payment is the last step. */
class DetailsBeforePaymentTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([CatalogueSeeder::class, MarketPriceSeeder::class, PujaActivationSeeder::class, CatalogueRevisionSeeder::class] as $s) {
            $this->seed($s);
        }
        $s = app(SettingsRepository::class);
        $s->set('payments.razorpay.key_id', 'rzp_test_'.str_repeat('D', 14));
        $s->set('payments.razorpay.key_secret', 'sec', secret: true);
        Http::fake(['api.razorpay.com/v1/orders' => fn ($r) => Http::response(['id' => 'order_D'.(++$this->seq), 'amount' => $r['amount']])]);
        Storage::fake('local');
        Mail::fake();
    }

    private function customer(): User
    {
        $u = new User(['name' => 'Ravi Kumar', 'email' => uniqid().'@example.com']);
        $u->save();

        return $u;
    }

    private function capture(object $o): void
    {
        $p = DB::table('payments')->where('order_id', $o->id)->first();
        $this->assertSame('applied', app(Checkout::class)->applyCapture($p->provider_order_id, 'pay_'.$o->id, (int) $p->amount_minor, 'upi'));
    }

    public function test_puja_sankalp_is_taken_before_payment(): void
    {
        $u = $this->customer();
        $v = Offering::where('slug', 'griha-pravesh-puja')->first()->variants()->where('name', 'At your home — Patna')->first();
        $this->actingAs($u)->get(route('checkout.details', $v->ref))->assertOk()->assertSee('Names for the sankalp');
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'p1', 'devotees' => [['name' => ''], ['name' => '']], 'location' => 'home', 'phone' => '9123456789'])
            ->assertSessionHasErrors('devotees');
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'p1', 'devotees' => [['name' => 'Ravi Kumar', 'relation' => 'Self'], ['name' => '']], 'gotra' => 'Kashyap',
            'location' => 'home', 'address' => 'Kankarbagh, Patna', 'phone' => '9123456789', 'preferred_date' => now()->addDays(10)->toDateString()])->assertRedirect();
        $o = DB::table('orders')->latest('id')->first();
        $this->assertStringNotContainsString('Kashyap', $o->snapshot, 'sankalp is encrypted in the order');
        $this->capture($o);
        $b = DB::table('puja_bookings')->where('order_id', $o->id)->first();
        $this->assertSame('requested', $b->status);
        $this->assertSame('Kashyap', PujaBookings::sankalp($b)['gotra']);
        $this->assertSame('home', $b->location);
    }

    public function test_report_birth_details_are_taken_before_payment(): void
    {
        $u = $this->customer();
        $v = Offering::where('slug', 'career-report')->first()->variants()->first();
        $this->actingAs($u)->get(route('checkout.details', $v->ref))->assertOk()->assertSee('Add birth details');
        $p = BirthProfile::forceCreate(['ref' => (string) Str::ulid(), 'user_id' => $u->id, 'label' => 'self', 'name' => 'Ravi', 'birth_date' => '1990-05-14', 'birth_time' => '06:30:00',
            'time_accuracy' => 'exact', 'place_label' => 'Patna', 'lat' => 25.59, 'lng' => 85.13, 'tz_name' => 'Asia/Kolkata', 'birth_utc' => '1990-05-14 01:00:00']);
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'r1', 'language' => 'hi'])->assertSessionHasErrors('profile_a');
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'r1', 'profile_a' => $p->ref, 'language' => 'hi', 'question' => 'Job change?'])->assertRedirect();
        $o = DB::table('orders')->latest('id')->first();
        $this->assertSame(0, DB::table('report_jobs')->count());
        if (! app(Engine::class)->available()) {
            $this->markTestSkipped('engine not built');
        }
        $this->capture($o);
        $j = DB::table('report_jobs')->where('order_id', $o->id)->first();
        $this->assertSame('in_preparation', $j->status);
        $this->assertSame('Job change?', $j->question);
    }

    public function test_site_visit_address_is_taken_before_payment_and_sent_to_the_team(): void
    {
        $u = $this->customer();
        $v = Offering::where('slug', 'vastu-consultation')->first()->variants()->where('name', 'On-site visit — outside Bihar')->first();
        $this->actingAs($u)->post(route('checkout.buy'), ['variant' => $v->ref, 'key' => 'x'])->assertRedirect(route('checkout.details', ['variant' => $v->ref, 'use_wallet' => 0]));
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'v1', 'phone' => '9123456789'])->assertSessionHasErrors(['address', 'city']);
        $this->post(route('checkout.details.submit', $v->ref), ['key' => 'v1', 'phone' => '9123456789', 'address' => 'Sector 5', 'city' => 'Noida'])->assertRedirect();
        $o = DB::table('orders')->latest('id')->first();
        $this->assertSame(2500000, (int) $o->subtotal_minor);
        $this->assertSame('Noida', ServiceDetails::visit(json_decode($o->snapshot, true))['city']);
    }

    public function test_app_sends_palm_photos_with_the_order(): void
    {
        $u = $this->customer();
        $token = $u->createToken('app', ['customer'])->plainTextToken;
        $v = Offering::where('slug', 'palmistry')->first()->variants()->where('mode', 'report')->first();
        $detail = $this->getJson('/api/v1/catalogue/offerings/palmistry')->json('data.variants');
        $this->assertSame('palm', collect($detail)->firstWhere('ref', $v->ref)['details']);
        $img = fn () => UploadedFile::fake()->image('p.jpg', 900, 1200);
        $this->withToken($token)->post('/api/v1/checkout/service', ['variant' => $v->ref, 'key' => 'a1', 'with_details' => '1', 'dominant_hand' => 'right', 'consent' => '1'], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['left', 'right']);
        $r = $this->withToken($token)->post('/api/v1/checkout/service', ['variant' => $v->ref, 'key' => 'a1', 'with_details' => '1', 'left' => $img(), 'right' => $img(), 'dominant_hand' => 'right', 'consent' => '1'], ['Accept' => 'application/json'])
            ->assertOk()->json();
        $this->assertSame('pending_payment', $r['order']['status']);
        $this->capture(DB::table('orders')->where('ref', $r['order']['ref'])->first());
        $this->assertSame('submitted', DB::table('palm_readings')->value('status'));
    }

    public function test_app_visitors_can_chat_without_signing_in_but_cannot_order(): void
    {
        $s = app(SettingsRepository::class);
        $s->set('ai.enabled', true);
        $s->set('ai.order', 'groq');
        $s->set('ai.groq.enabled', true);
        $s->set('ai.groq.key', 'k', secret: true);
        $sent = [];
        Http::fake(['api.groq.com/*' => function ($req) use (&$sent) {
            $sent[] = $req->data();

            return Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'Namaste']]]]);
        }]);
        $this->postJson('/api/v1/assistant/chat', ['message' => 'Puja ka price?'])->assertOk()->assertJsonPath('reply', 'Namaste');
        $this->assertNotContains('start_order', array_column(array_column($sent[0]['tools'], 'function'), 'name'));
        $u = $this->customer();
        $this->withToken($u->createToken('app', ['customer'])->plainTextToken)->postJson('/api/v1/assistant/chat', ['message' => 'Book karo'])->assertOk();
        $this->assertContains('start_order', array_column(array_column($sent[1]['tools'], 'function'), 'name'));
    }
}
