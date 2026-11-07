<?php

namespace Tests\Feature\Website;

use App\Support\SiteContent;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Admin › Website & home page controls the announcement bar, the contact bar and the home sections. */
class WebsiteControlTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        $this->syncRbac();
    }

    public function test_publisher_controls_announcement_contact_bar_and_sections(): void
    {
        $this->get('/')->assertOk()->assertSee('Choose how you want guidance')->assertSee('Browse by category')->assertDontSee('aria-label="Announcement"', false);
        $this->asStaff($this->makeStaff(['content_author']))->get(route('admin.website.edit'))->assertForbidden();

        $pub = $this->makeStaff(['content_publisher']);
        $this->asStaff($pub)->get(route('admin.website.edit'))->assertOk()->assertSee('Home page sections');
        $sections = array_values(array_diff(array_keys(SiteContent::SECTIONS), ['families']));
        $this->asStaff($pub)->put(route('admin.website.update'), ['announce_on' => '1', 'announce_text' => 'Diwali puja bookings are open', 'announce_link' => 'javascript:alert(1)',
            'sections' => $sections])->assertSessionHasErrors('announce_link');
        $this->asStaff($pub)->put(route('admin.website.update'), ['announce_on' => '1', 'announce_text' => 'Diwali puja bookings are open', 'announce_link' => '/services?family=puja',
            'announce_cta' => 'Book now', 'utility_text' => 'Namaste from Patna', 'utility_on' => '1', 'sections' => $sections, 'order' => ['articles' => 1]])->assertSessionHasNoErrors();

        $html = $this->get('/')->assertOk()->assertSee('Diwali puja bookings are open')->assertSee('Book now')->assertSee('Namaste from Patna')->assertDontSee('Browse by category')->getContent();
        $this->assertLessThan(strpos($html, 'Choose how you want guidance'), strpos($html, 'Learn from the blog') ?: strpos($html, 'art-h'));

        $this->asStaff($pub)->put(route('admin.website.update'), ['sections' => $sections])->assertSessionHasNoErrors();
        $this->get('/faq')->assertDontSee('Diwali puja bookings are open')->assertDontSee('Namaste from Patna');
    }

    public function test_dashboard_and_sidebar(): void
    {
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.dashboard'))->assertOk()->assertSee('Revenue, last 30 days')->assertSee('Website &amp; home page', false)->assertSee('Find a section');
        $this->asStaff($this->makeStaff(['support']))->get(route('admin.dashboard'))->assertOk()->assertDontSee('Revenue, last 30 days')->assertDontSee('Payments setup');
    }
}
