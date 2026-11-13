<?php

namespace Tests\Feature\Website;

use App\Domain\Analytics\GeoIp;
use App\Domain\Analytics\Tracker;
use App\Support\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** First-party visitor analytics for the website and the app, shown in Admin › Analytics. */
class AnalyticsTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->syncRbac();
        config(['analytics.trust_cdn_country' => true]);
    }

    private const PHONE = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Mobile Safari/537.36';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36 Edg/129.0';

    public function test_page_views_record_visitor_session_device_country_and_campaign(): void
    {
        $first = $this->withHeaders(['User-Agent' => self::PHONE, 'CF-IPCountry' => 'AE', 'Referer' => 'https://www.google.com/search?q=kundli'])
            ->get('/faq?utm_source=instagram&utm_medium=social&utm_campaign=diwali')->assertOk();
        $vid = $first->getCookie(Tracker::VISITOR_COOKIE)?->getValue();
        $this->assertNotNull($vid);
        $e = DB::table('analytics_events')->where('type', 'pageview')->first();
        $this->assertSame('/faq', $e->path);
        $this->assertSame('faq', $e->route);
        $this->assertSame('mobile', $e->device);
        $this->assertSame('Android', $e->os);
        $this->assertSame('Chrome', $e->browser);
        $this->assertSame('AE', $e->country);
        $this->assertSame('google.com', $e->referrer);
        $this->assertSame('diwali', $e->utm_campaign);
        $this->assertSame(1, (int) $e->is_new);

        // a desktop visitor; then bots, the control centre and "do not track" are not counted
        $this->withHeaders(['User-Agent' => self::DESKTOP])->get('/contact')->assertOk();
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get('/contact');
        $this->withHeaders(['User-Agent' => self::DESKTOP, 'Sec-GPC' => '1'])->get('/contact');
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->withHeaders(['User-Agent' => self::DESKTOP])->get('/admin');
        $this->assertSame(2, DB::table('analytics_events')->where('type', 'pageview')->count());
        $this->assertSame('Edge', DB::table('analytics_events')->where('path', '/contact')->value('browser'));

        $page = $this->asStaff($owner)->get(route('admin.analytics.website', ['days' => 7]))->assertOk();
        $page->assertSee('/faq')->assertSee('United Arab Emirates')->assertSee('google.com')->assertSee('instagram / social / diwali');
        $this->assertSame(2, $page->viewData('totals')['visitors']);
    }

    public function test_clicks_and_time_on_page_come_from_the_beacon(): void
    {
        $res = $this->withHeaders(['User-Agent' => self::PHONE])->get('/faq');
        $vid = $res->getCookie(Tracker::VISITOR_COOKIE)->getValue();
        $sid = $res->getCookie(Tracker::SESSION_COOKIE)->getValue();
        $beacon = fn (array $d) => $this->withHeaders(['User-Agent' => self::PHONE])->withCookie(Tracker::VISITOR_COOKIE, $vid)->withCookie(Tracker::SESSION_COOKIE, $sid)
            ->post(route('analytics.event'), $d + ['path' => '/faq']);
        $beacon(['type' => 'click', 'label' => '  Free   Kundli ', 'target' => '/tools/kundli?name=Ravi#x'])->assertNoContent();
        $beacon(['type' => 'engage', 'seconds' => 75])->assertNoContent();
        $beacon(['type' => 'click', 'label' => 'Call', 'target' => 'tel:+919835258203'])->assertNoContent();
        $click = DB::table('analytics_events')->where('type', 'click')->first();
        $this->assertSame('Free Kundli', $click->label);
        $this->assertSame('/tools/kundli', $click->target);   // the query string is never kept
        $this->assertSame('tel:', DB::table('analytics_events')->where('label', 'Call')->value('target'));
        $this->assertSame($sid, $click->session);

        $page = $this->asStaff($this->makeStaff(['administrator']))->get(route('admin.analytics.website'))->assertOk()->assertSee('Free Kundli');
        $this->assertSame(75, $page->viewData('totals')['avg_seconds']);
        $this->assertSame(100, $page->viewData('totals')['bounce_rate']);

        // a browser that never loaded a page (no visitor cookie) is ignored
        $this->defaultCookies = [];
        $this->withHeaders(['User-Agent' => self::PHONE])->post(route('analytics.event'), ['type' => 'click', 'path' => '/', 'label' => 'x'])->assertNoContent();
        $this->assertSame(2, DB::table('analytics_events')->where('type', 'click')->count());
    }

    public function test_app_opens_features_and_screens(): void
    {
        $app = ['User-Agent' => 'Dart/3.9 (dart:io)', 'X-App-Install' => 'c0ffee00-1111-2222-3333-444455556666', 'X-App-Version' => '1.13.0', 'CF-IPCountry' => 'IN'];
        $this->withHeaders($app)->getJson('/api/v1/meta')->assertOk();
        $this->withHeaders($app)->getJson('/api/v1/faq')->assertOk();
        $this->withHeaders($app)->postJson('/api/v1/analytics/events', ['events' => [['type' => 'screen', 'name' => 'Panchang'], ['type' => 'screen', 'name' => 'Kundli']]])->assertNoContent();
        $this->flushHeaders()->withHeaders(['User-Agent' => 'Dart/3.9 (dart:io)'])->getJson('/api/v1/meta')->assertOk();   // an older build, no install id

        $this->assertSame(2, DB::table('analytics_events')->where('type', 'app_open')->count());
        $this->assertSame('1.13.0', DB::table('analytics_events')->where('type', 'app_open')->whereNotNull('app_version')->value('app_version'));
        $page = $this->asStaff($this->makeStaff(['owner']))->get(route('admin.analytics.app'))->assertOk()->assertSee('FAQ')->assertSee('Panchang')->assertSee('1.13.0');
        $this->assertSame(2, $page->viewData('totals')['devices']);
        // failed app requests (not found, validation, throttled) are not feature use
        $before = DB::table('analytics_events')->count();
        $this->withHeaders($app)->getJson('/api/v1/blog/no-such-article')->assertNotFound();
        $this->assertSame($before, DB::table('analytics_events')->count());
        // a country header is ignored unless the site is behind a CDN that sets it
        config(['analytics.trust_cdn_country' => false]);
        $this->withHeaders($app)->getJson('/api/v1/meta');
        $this->assertNull(DB::table('analytics_events')->orderByDesc('id')->value('country'));
        // website visits are not app opens, and the app does not count as website visitors
        $this->assertSame(0, DB::table('analytics_events')->where('channel', 'web')->count());
    }

    public function test_geoip_import_lookup_settings_and_retention(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'geo');
        file_put_contents($file, "1.0.0.0,1.0.0.255,AU\n49.32.0.0,49.47.255.255,IN\n2405:200::,2405:3ff:ffff:ffff:ffff:ffff:ffff:ffff,IN\n");
        $this->assertSame(0, Artisan::call('arj:geoip-import', ['--file' => $file]));
        $geo = app(GeoIp::class);
        $this->assertSame('IN', $geo->country('49.36.10.1'));
        $this->assertSame('AU', $geo->country('1.0.0.7'));
        $this->assertSame('IN', $geo->country('2405:201::1'));
        $this->assertNull($geo->country('8.8.8.8'));
        unlink($file);

        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->put(route('admin.analytics.settings'), ['enabled' => '0', 'retention_days' => 60])->assertRedirect();
        $this->withHeaders(['User-Agent' => self::PHONE])->get('/faq');
        $this->assertSame(0, DB::table('analytics_events')->count());

        app(SettingsRepository::class)->set('analytics.enabled', true);
        DB::table('analytics_events')->insert([['occurred_at' => now()->subDays(61), 'type' => 'pageview', 'channel' => 'web'], ['occurred_at' => now()->subDays(5), 'type' => 'pageview', 'channel' => 'web']]);
        Artisan::call('arj:analytics-prune');
        $this->assertSame(1, DB::table('analytics_events')->count());
    }

    public function test_only_permitted_staff_see_analytics(): void
    {
        $this->asStaff($this->makeStaff(['support']))->get(route('admin.analytics.website'))->assertForbidden();
        $this->asStaff($this->makeStaff(['content_publisher']))->get(route('admin.analytics.website'))->assertOk();
    }
}
