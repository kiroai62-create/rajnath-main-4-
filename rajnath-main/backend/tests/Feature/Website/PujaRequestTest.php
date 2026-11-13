<?php

namespace Tests\Feature\Website;

use App\Models\PujaRequest;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Database\Seeders\PujaActivationSeeder;
use Database\Seeders\ServiceContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Online puja and chadhava are gone; pujas are requested with a form and fixed by staff in the admin. */
class PujaRequestTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    private function valid(array $extra = []): array
    {
        return array_merge([
            'name' => 'Ramesh Kumar', 'phone' => '98765 43210', 'email' => 'ramesh@example.com',
            'puja_type' => 'Rudrabhishek', 'purpose' => 'Health of my father', 'mode' => 'online',
            'preferred_date' => now('Asia/Kolkata')->addDays(5)->toDateString(), 'preferred_time' => 'morning',
            'participants' => 'Ramesh Kumar, Sunita Devi', 'gotra' => 'Kashyap', 'language' => 'Hindi', 'samagri' => 'we_arrange',
            'consent' => '1',
        ], $extra);
    }

    public function test_menu_and_old_puja_pages_lead_to_the_request_form(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        $this->seed(ServiceContentSeeder::class);
        $this->seed(PujaActivationSeeder::class);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString(route('puja-request.create'), $html);
        $this->assertStringNotContainsString('All chadhava', $html);
        $this->assertStringNotContainsString(route('services.show', 'rudrabhishek'), $html);
        $this->assertStringNotContainsString('?family=chadhava', $html);

        $this->get('/services?family=puja')->assertRedirect(route('puja-request.create'));
        $this->get('/services/rudrabhishek')->assertRedirect(route('puja-request.create', ['puja' => 'Rudrabhishek']));
        $this->assertFalse(collect($this->get('/services')->viewData('offerings'))->contains(fn ($o) => in_array($o->family->code, ['puja', 'chadhava'], true)));
        $this->assertStringNotContainsString('/services/rudrabhishek', $this->get('/sitemap.xml')->getContent());
        $this->getJson('/api/v1/catalogue/offerings/rudrabhishek')->assertNotFound();

        $this->get(route('puja-request.create', ['puja' => 'Rudrabhishek']))->assertOk()
            ->assertSee('Tell us the puja you need')->assertSee('<option value="Rudrabhishek" selected>', false);
    }

    public function test_visitor_sends_a_request_and_staff_fix_the_date(): void
    {
        $this->syncRbac();
        $this->post(route('puja-request.store'), $this->valid())->assertRedirect();
        $r = PujaRequest::firstOrFail();
        $this->assertSame('+919876543210', $r->phone_e164);
        $this->assertSame('Rudrabhishek', $r->puja_type);
        $this->assertSame('new', $r->status);
        $this->get(route('puja-request.thanks', $r->ref))->assertOk()->assertSee('Ramesh Kumar');

        $admin = $this->makeStaff(['administrator']);
        $this->asStaff($admin)->get(route('admin.puja-requests.index'))->assertOk()->assertSee('Ramesh Kumar')->assertSee('Rudrabhishek');
        $this->asStaff($admin)->get(route('admin.puja-requests.show', $r))->assertOk()->assertSee('Health of my father')->assertSee('Kashyap');
        $this->asStaff($admin)->put(route('admin.puja-requests.update', $r), ['status' => 'scheduled'])->assertSessionHasErrors('scheduled_date');
        $this->asStaff($admin)->put(route('admin.puja-requests.update', $r), [
            'status' => 'scheduled', 'scheduled_date' => now('Asia/Kolkata')->addDays(6)->toDateString(), 'scheduled_time' => '07:30',
            'priest' => 'Pt. Mishra', 'quoted' => '5100', 'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ])->assertRedirect();
        $r->refresh();
        $this->assertSame('scheduled', $r->status);
        $this->assertSame(510000, $r->quoted_minor);
        $this->assertSame('07:30', $r->scheduled_at->tz('Asia/Kolkata')->format('H:i'));
        $this->assertTrue(DB::table('audit_logs')->where('action', 'puja_request.updated')->exists());

        $support = $this->makeStaff(['support']);   // can view, cannot change
        $this->asStaff($support)->get(route('admin.puja-requests.show', $r))->assertOk();
        $this->asStaff($support)->put(route('admin.puja-requests.update', $r), ['status' => 'cancelled'])->assertForbidden();
    }

    public function test_signed_in_customer_sees_requests_and_is_notified(): void
    {
        $this->syncRbac();
        $c = User::factory()->create(['phone_e164' => '+919876543210']);
        $this->actingAs($c)->post(route('puja-request.store'), $this->valid(['mode' => 'home', 'address' => 'Kankarbagh, Patna']))->assertRedirect();
        $r = PujaRequest::firstOrFail();
        $this->assertSame($c->id, $r->user_id);
        $this->actingAs($c)->get(route('account.puja.index'))->assertOk()->assertSee('Rudrabhishek');
        $admin = $this->makeStaff(['owner']);
        $this->asStaff($admin)->put(route('admin.puja-requests.update', $r), ['status' => 'scheduled', 'scheduled_date' => now()->addDays(3)->toDateString()])->assertRedirect();
        $this->assertTrue(DB::table('user_notifications')->where('user_id', $c->id)->where('title', 'Puja date fixed')->exists());
    }

    public function test_validation_honeypot_and_form_settings(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);   // many posts from one IP in one test
        $this->syncRbac();
        $this->post(route('puja-request.store'), $this->valid(['phone' => '123']))->assertSessionHasErrors('phone');
        $this->post(route('puja-request.store'), $this->valid(['mode' => 'home', 'address' => '']))->assertSessionHasErrors('address');
        $this->post(route('puja-request.store'), $this->valid(['puja_type' => 'Not in the list']))->assertSessionHasErrors('puja_type');
        $this->post(route('puja-request.store'), $this->valid(['puja_type' => 'other', 'puja_other' => 'Tulsi Vivah']))->assertRedirect();
        $this->assertSame('Tulsi Vivah', PujaRequest::latest('id')->value('puja_type'));
        $this->post(route('puja-request.store'), $this->valid(['website' => 'spam']))->assertRedirect();
        $this->assertSame(1, PujaRequest::count());

        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->put(route('admin.puja-requests.settings'), ['types' => "Tulsi Vivah\nRudrabhishek", 'intro' => 'Hello', 'open' => '1'])->assertRedirect();
        $this->get(route('puja-request.create'))->assertSee('<option value="Tulsi Vivah"', false)->assertDontSee('<option value="Lakshmi Puja"', false);
        $this->asStaff($owner)->put(route('admin.puja-requests.settings'), ['types' => 'Rudrabhishek', 'open' => '0'])->assertRedirect();
        $this->get(route('puja-request.create'))->assertSee('Puja requests are paused');
    }
}
