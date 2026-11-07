<?php

namespace Tests\Feature\Phase13;

use App\Support\AppConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Admin › Mobile app drives GET /api/v1/meta → app. */
class MobileAppControlTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
    }

    public function test_defaults_keep_everything_on(): void
    {
        $app = $this->getJson('/api/v1/meta')->assertOk()->json('app');
        $this->assertFalse($app['maintenance']['on']);
        $this->assertNull($app['announcement']);
        $this->assertSame([], $app['home']['hidden']);
        $this->assertSame('greeting', $app['home']['order'][0]);
        $this->assertTrue($app['features']['assistant']);
        $this->assertTrue($app['features']['store']);
    }

    public function test_owner_controls_the_app_from_admin(): void
    {
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.app.edit'))->assertOk()->assertSee('App home screen sections')->assertSee('Maintenance');
        $this->asStaff($owner)->put(route('admin.app.update'), [
            'announce_on' => '1', 'announce_text' => 'Diwali puja bookings open', 'announce_cta' => 'Book', 'announce_link' => 'app:puja', 'announce_style' => 'maroon',
            'sections' => ['hero', 'ecosystem', 'signs'], 'order' => ['signs' => 1, 'hero' => 2, 'ecosystem' => 3],
            'features' => ['assistant', 'puja', 'consultation'],
            'maintenance_on' => '1', 'maintenance_text' => 'Back at 6 pm', 'min_version' => '1.11.0', 'latest_version' => '1.12.0',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $meta = $this->getJson('/api/v1/meta')->assertOk()->json();
        $this->assertSame('1.11.0', $meta['min_app_version']);
        $app = $meta['app'];
        $this->assertTrue($app['maintenance']['on']);
        $this->assertSame('Back at 6 pm', $app['maintenance']['message']);
        $this->assertSame('1.12.0', $app['update']['latest_version']);
        $this->assertSame(['text' => 'Diwali puja bookings open', 'cta' => 'Book', 'link' => 'app:puja', 'style' => 'maroon'], $app['announcement']);
        $pos = array_flip($app['home']['order']);
        $this->assertTrue($pos['signs'] < $pos['hero'] && $pos['hero'] < $pos['ecosystem']);
        $this->assertContains('greeting', $app['home']['hidden']);
        $this->assertNotContains('hero', $app['home']['hidden']);
        $this->assertFalse($app['features']['store']);
        $this->assertTrue($app['features']['puja']);
    }

    public function test_content_staff_cannot_lock_the_app_and_bad_input_is_refused(): void
    {
        $publisher = $this->makeStaff(['content_publisher']);
        $this->asStaff($publisher)->get(route('admin.app.edit'))->assertOk();
        $this->asStaff($publisher)->put(route('admin.app.update'), ['maintenance_on' => '1', 'min_version' => '9.0.0', 'features' => array_keys(AppConfig::FEATURES)])->assertSessionHasNoErrors();
        $app = $this->getJson('/api/v1/meta')->json('app');
        $this->assertFalse($app['maintenance']['on']);
        $this->assertNotSame('9.0.0', $app['update']['min_version']);

        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->put(route('admin.app.update'), ['announce_link' => 'javascript:alert(1)'])->assertSessionHasErrors('announce_link');
        $this->asStaff($owner)->put(route('admin.app.update'), ['min_version' => '2.0.0', 'latest_version' => '1.0.0'])->assertSessionHasErrors('latest_version');
        $this->asStaff($this->makeStaff(['support']))->get(route('admin.app.edit'))->assertForbidden();
    }
}
