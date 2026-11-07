<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Branded error pages, request IDs and the extra security headers. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        Route::middleware('web')->group(function () {
            Route::get('/__test/boom', fn () => throw new \RuntimeException('secret-internal-detail SQLSTATE'));
            Route::get('/__test/abort/{code}', fn (int $code) => abort($code));
            Route::get('/__test/throttled', fn () => throw new TooManyRequestsHttpException(120));
        });
    }

    public function test_404_is_branded_noindex_and_offers_popular_pages(): void
    {
        $r = $this->get('/this-page-does-not-exist')->assertNotFound();
        $r->assertSee('Page not found')->assertSee('Popular pages')->assertSee(route('services.index'), false)
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertNotEmpty($r->headers->get('X-Request-Id'));
        $this->assertStringContainsString("frame-ancestors 'none'", $r->headers->get('Content-Security-Policy'));
    }

    public function test_api_404_stays_json(): void
    {
        $this->getJson('/api/v1/nope')->assertNotFound()->assertJsonStructure(['message']);
        $this->get('/api/v1/nope')->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_500_hides_the_exception_and_shows_a_reference(): void
    {
        $r = $this->withHeaders(['X-Request-Id' => 'abcDEF123456'])->get('/__test/boom');
        $r->assertStatus(500)->assertSee('Something went wrong')->assertSee('abcDEF123456')
            ->assertDontSee('secret-internal-detail')->assertDontSee('SQLSTATE')->assertDontSee('RuntimeException');
        $r->assertHeader('X-Request-Id', 'abcDEF123456');
    }

    public function test_untrusted_request_ids_are_replaced(): void
    {
        $id = $this->withHeaders(['X-Request-Id' => '<script>alert(1)</script>'])->get('/faq')->headers->get('X-Request-Id');
        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $id);
    }

    public function test_other_error_pages(): void
    {
        $this->get('/__test/abort/403')->assertForbidden()->assertSee('You do not have access to this page');
        $this->get('/__test/abort/419')->assertStatus(419)->assertSee('This page has expired');
        $this->get('/__test/abort/503')->assertStatus(503)->assertSee('We will be right back');
        $this->get('/__test/abort/401')->assertStatus(401)->assertSee('Please sign in')->assertSee(route('account.sign-in'), false);
        $this->get('/__test/abort/413')->assertStatus(413)->assertSee('That upload is too large');
        // fallbacks for codes without their own page
        $this->get('/__test/abort/410')->assertStatus(410)->assertSee('We could not handle that request')->assertSee('410');
        $this->get('/__test/abort/502')->assertStatus(502)->assertSee('Something went wrong')->assertSee('502');
        $this->get('/__test/throttled')->assertStatus(429)->assertSee('Too many attempts')->assertSee('2 minutes');
    }

    public function test_admin_errors_point_back_to_the_control_centre(): void
    {
        $this->syncRbac();
        $this->asStaff($this->makeStaff(['content_author']))->get('/admin/team')->assertForbidden()
            ->assertSee('Back to the control centre')->assertSee('Your role does not include this section');
        $this->get('/admin/no-such-section')->assertNotFound()->assertDontSee('Popular pages');
    }

    public function test_go_back_link_only_for_same_site_referrers(): void
    {
        $this->withHeaders(['Referer' => 'https://evil.example/phish'])->get('/missing-page')->assertNotFound()->assertDontSee('evil.example');
        $this->withHeaders(['Referer' => url('/faq')])->get('/missing-page')->assertSee('Go back')->assertSee(url('/faq'), false);
    }

    public function test_extra_security_headers_and_security_txt(): void
    {
        $r = $this->get('/');
        $r->assertHeader('Cross-Origin-Resource-Policy', 'same-site');
        $r->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $this->assertFalse($r->headers->has('X-Powered-By'));
        $this->assertStringContainsString('usb=()', $r->headers->get('Permissions-Policy'));
        $this->get('/.well-known/security.txt')->assertOk()
            ->assertSee('Contact: mailto:')->assertSee('Expires: ')
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}
