<?php

namespace Tests\Feature\Foundation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** The first admin is created on deploy; staff manage their own password and two-factor sign-in. */
class AdminAccountTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    public function test_deploy_creates_the_admin_once_and_it_can_sign_in(): void
    {
        config(['rbac.bootstrap_admin' => ['email' => 'Owner@Example.com', 'name' => 'Acharya Ji', 'password' => null]]);
        $this->assertSame(0, Artisan::call('arj:ensure-admin'));
        preg_match('/Password:\s+(\S+)/', Artisan::output(), $m);
        $password = $m[1];
        $admin = User::where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('owner'));
        $this->assertTrue($admin->password_must_change);
        $this->assertGreaterThanOrEqual(16, strlen($password));

        // a second deploy changes nothing
        Artisan::call('arj:ensure-admin', ['--password' => 'Another-Pass-123!']);
        $this->assertTrue(Hash::check($password, $admin->fresh()->password));
        $this->assertSame(1, User::role('owner')->count());

        $this->post('/staff/login', ['email' => 'owner@example.com', 'password' => $password])->assertRedirect(route('admin.account.show'));
        $this->get('/admin')->assertOk()->assertSee('Please choose your own password.');
        $this->put(route('admin.account.password'), ['current_password' => $password, 'password' => 'My-Own-Secret-42!', 'password_confirmation' => 'My-Own-Secret-42!'])->assertSessionHasNoErrors();
        $this->assertFalse($admin->fresh()->password_must_change);
        $this->get(route('admin.manual'))->assertOk()->assertSee('Puja requests')->assertSee('Languages and translation');
    }

    public function test_reset_command_recovers_a_forgotten_password(): void
    {
        $this->syncRbac();
        $owner = $this->makeStaff(['owner'], ['email' => 'boss@example.com']);
        Artisan::call('arj:ensure-admin', ['--reset' => true, '--email' => 'boss@example.com', '--password' => 'Fresh-Start-2026!']);
        $owner->refresh();
        $this->assertTrue(Hash::check('Fresh-Start-2026!', $owner->password));
        $this->assertFalse($owner->hasMfa());
    }

    public function test_staff_change_email_and_turn_two_factor_on_and_off(): void
    {
        $this->syncRbac();
        $u = $this->makeStaff(['support'], ['email' => 'old@staff.test']);
        $u->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null])->save();
        $this->asStaff($u)->put(route('admin.account.profile'), ['name' => 'New Name', 'email' => 'new@staff.test', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->asStaff($u)->put(route('admin.account.profile'), ['name' => 'New Name', 'email' => 'new@staff.test', 'current_password' => 'Correct-Horse-9!'])->assertSessionHasNoErrors();
        $this->assertSame('new@staff.test', $u->fresh()->email);

        $this->asStaff($u)->get(route('admin.account.show'))->assertOk()->assertSee('Turn on two-factor sign-in');
        $secret = session('account_mfa_secret');
        $this->asStaff($u)->withSession(['account_mfa_secret' => $secret])->post(route('admin.account.mfa.enable'), ['code' => (new Google2FA)->getCurrentOtp($secret), 'current_password' => 'Correct-Horse-9!'])->assertSessionHasNoErrors();
        $this->assertTrue($u->fresh()->hasMfa());

        // with 2FA on, sign-in asks for the code
        $this->post('/staff/logout');
        $this->post('/staff/login', ['email' => 'new@staff.test', 'password' => 'Correct-Horse-9!'])->assertRedirect('/staff/mfa');
    }

    public function test_owner_adds_staff_directly_and_resets_their_password(): void
    {
        $this->syncRbac();
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->post(route('admin.team.store'), ['name' => 'Priya', 'email' => 'priya@staff.test', 'password' => 'Start-Here-2026!', 'roles' => ['support']])->assertSessionHasNoErrors();
        $priya = User::where('email', 'priya@staff.test')->firstOrFail();
        $this->assertTrue($priya->hasRole('support'));
        $this->asStaff($owner)->put(route('admin.team.password', $priya), ['password' => 'Second-Try-2026!'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Second-Try-2026!', $priya->fresh()->password));

        $admin = $this->makeStaff(['administrator']);   // cannot touch an owner
        $this->asStaff($admin)->put(route('admin.team.password', $owner), ['password' => 'Take-Over-2026!'])->assertForbidden();
        $this->asStaff($admin)->post(route('admin.team.store'), ['name' => 'X', 'email' => 'x@staff.test', 'password' => 'Start-Here-2026!', 'roles' => ['owner']])->assertForbidden();
    }
}
