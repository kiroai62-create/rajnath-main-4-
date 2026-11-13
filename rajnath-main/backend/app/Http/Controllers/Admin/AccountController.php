<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Mfa;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin › My account: every staff member changes their own name, sign-in email, password and
 * two-factor sign-in here. Each change asks for the current password and is audited.
 */
class AccountController extends Controller
{
    private const MFA_SESSION = 'account_mfa_secret';

    public function __construct(private AuditLogger $audit, private Mfa $mfa) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $secret = null;
        $qr = null;
        if (! $user->hasMfa()) {
            $secret = $request->session()->get(self::MFA_SESSION) ?? $this->mfa->newSecret();
            $request->session()->put(self::MFA_SESSION, $secret);
            $qr = $this->mfa->qrSvg($user->email, $secret);
        }

        return view('admin.account.show', ['user' => $user, 'secret' => $secret, 'qr' => $qr]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string'],
        ]);
        $this->checkPassword($user, $data['current_password']);
        $before = $user->email;
        $user->forceFill(['name' => $data['name'], 'email' => strtolower($data['email'])])->save();
        $this->audit->record('staff.profile_changed', $user, ['email_changed' => $before !== $user->email], $user);

        return back()->with('status', 'Name and email saved.'.($before !== $user->email ? ' Use the new email next time you sign in.' : ''));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', StaffAuthController::passwordRule()],
        ]);
        $this->checkPassword($user, $data['current_password']);
        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['password' => 'Choose a password different from the current one.']);
        }
        $user->forceFill(['password' => $data['password'], 'password_changed_at' => now(), 'password_must_change' => false])->save();
        $request->session()->regenerate();
        $this->audit->record('staff.password_changed', $user, [], $user);

        return back()->with('status', 'Password changed.');
    }

    public function enableMfa(Request $request): RedirectResponse
    {
        $user = $request->user();
        $secret = $request->session()->get(self::MFA_SESSION);
        $data = $request->validate(['code' => ['required', 'string'], 'current_password' => ['required', 'string']]);
        $this->checkPassword($user, $data['current_password']);
        if (! $secret || ! $this->mfa->verifySecret($secret, $data['code'], 'u'.$user->id)) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is not valid. Check your phone clock and try again.']);
        }
        $user->forceFill(['mfa_secret' => $secret, 'mfa_confirmed_at' => now()])->save();
        $request->session()->forget(self::MFA_SESSION);
        $this->audit->record('staff.mfa_enabled', $user, [], $user);

        return back()->with('status', 'Two-factor sign-in is on. You will be asked for a code from the app at every sign-in.');
    }

    public function disableMfa(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['current_password' => ['required', 'string'], 'code' => ['required', 'string']]);
        $this->checkPassword($user, $data['current_password']);
        if (! $this->mfa->verify($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is not valid.']);
        }
        $user->forceFill(['mfa_secret' => null, 'mfa_confirmed_at' => null])->save();
        $this->audit->record('staff.mfa_disabled', $user, [], $user);

        return back()->with('status', 'Two-factor sign-in is off. Only your password is needed to sign in.');
    }

    private function checkPassword(User $user, string $password): void
    {
        if (! Hash::check($password, (string) $user->password)) {
            $this->audit->record('staff.account_change_failed', $user, [], $user);
            throw ValidationException::withMessages(['current_password' => 'Your current password is not correct.']);
        }
    }
}
