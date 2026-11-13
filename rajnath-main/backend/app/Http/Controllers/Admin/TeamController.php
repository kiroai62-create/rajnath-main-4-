<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\StaffInvitations;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        $this->authorize('team.view');
        $staff = User::query()->whereHas('roles')->with('roles')->orderBy('name')->paginate(25);
        $invitations = DB::table('staff_invitations')->whereNull('accepted_at')->whereNull('revoked_at')
            ->where('expires_at', '>', now())->orderByDesc('id')->get();

        return view('admin.team.index', ['staff' => $staff, 'invitations' => $invitations, 'roles' => $this->assignableRoles()]);
    }

    public function invite(Request $request, StaffInvitations $inv): RedirectResponse
    {
        $this->authorize('team.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($this->assignableRoles())],
        ]);
        [, $token] = $inv->create($data['email'], $data['name'], $data['roles'], $request->user());

        // Link is displayed once to the inviter; email delivery is added with the SMTP provider (phase 3).
        return back()->with('invite_link', url('/staff/invitation/'.$token));
    }

    /** Adds a staff member straight away with a password chosen by the admin (no invitation link). */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('team.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', StaffAuthController::passwordRule()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($this->assignableRoles())],
        ]);
        $email = strtolower($data['email']);
        $existing = User::where('email', $email)->first();
        if ($existing && $existing->isStaff()) {
            throw ValidationException::withMessages(['email' => 'A staff member with this email already exists. Use "Manage" in the list.']);
        }
        $this->guardOwnerChanges($request->user(), $existing ?? new User, $data['roles']);
        $user = $existing ?? new User(['email' => $email]);
        $user->name = $data['name'];
        $user->kind = User::KIND_STAFF;
        $user->status = User::STATUS_ACTIVE;
        $user->password = $data['password'];
        $user->password_changed_at = now();
        $user->password_must_change = true;
        $user->email_verified_at ??= now();
        $user->save();
        $user->syncRoles($data['roles']);
        $this->audit->record('staff.created', $user, ['roles' => $data['roles']]);

        return back()->with('status', $user->name.' can now sign in at '.route('staff.login').' with '.$email.' and the password you chose (they will be asked to change it).');
    }

    /** Sets a new password for a staff member who forgot theirs; optionally turns their 2FA off (lost phone). */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('team.manage');
        abort_unless($user->isStaff() || $user->roles()->exists(), 404);
        if ($user->hasRole('owner') && ! $request->user()->hasRole('owner')) {
            abort(403, 'Only an owner can reset an owner\'s password.');
        }
        $data = $request->validate([
            'password' => ['nullable', 'required_without:disable_mfa', StaffAuthController::passwordRule()],
            'disable_mfa' => ['nullable', 'boolean'],
        ]);
        $changes = [];
        if (! empty($data['password'])) {
            $changes += ['password' => $data['password'], 'password_changed_at' => now(), 'password_must_change' => ! $user->is($request->user())];
        }
        if (! empty($data['disable_mfa'])) {
            $changes += ['mfa_secret' => null, 'mfa_confirmed_at' => null];
        }
        $user->forceFill($changes)->save();
        if (! $user->is($request->user())) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $this->audit->record('staff.password_reset', $user, ['password' => ! empty($data['password']), 'mfa_off' => ! empty($data['disable_mfa'])]);

        return back()->with('status', 'Saved for '.$user->name.'.'.(! empty($data['password']) ? ' Share the new password with them privately.' : ''));
    }

    public function revokeInvitation(Request $request, string $ref, StaffInvitations $inv): RedirectResponse
    {
        $this->authorize('team.manage');
        $inv->revoke($ref, $request->user());

        return back()->with('status', 'Invitation revoked.');
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $this->authorize('team.manage');
        $data = $request->validate(['roles' => ['array'], 'roles.*' => [Rule::in($this->assignableRoles())]]);
        $new = $data['roles'] ?? [];
        $this->guardOwnerChanges($request->user(), $user, $new);
        $before = $user->getRoleNames()->all();
        $user->syncRoles($new);
        $this->audit->record('staff.roles_changed', $user, ['before' => $before, 'after' => $new]);

        return back()->with('status', 'Roles updated for '.$user->name.'.');
    }

    public function setStatus(Request $request, User $user): RedirectResponse
    {
        $this->authorize('team.manage');
        $data = $request->validate(['status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_SUSPENDED])]]);
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['status' => 'You cannot change your own status.']);
        }
        if ($user->hasRole('owner') && ! $request->user()->hasRole('owner')) {
            abort(403);
        }
        if ($data['status'] === User::STATUS_SUSPENDED && $user->hasRole('owner') && $this->activeOwners() <= 1) {
            throw ValidationException::withMessages(['status' => 'The last active owner cannot be suspended.']);
        }
        $user->forceFill(['status' => $data['status']])->save();
        if ($data['status'] === User::STATUS_SUSPENDED) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();
        }
        $this->audit->record('staff.status_changed', $user, ['status' => $data['status']]);

        return back()->with('status', 'Status updated.');
    }

    private function guardOwnerChanges(User $actor, User $target, array $newRoles): void
    {
        $touchesOwner = $target->hasRole('owner') || in_array('owner', $newRoles, true);
        if ($touchesOwner && ! $actor->hasRole('owner')) {
            abort(403, 'Only an owner can change owner roles.');
        }
        if ($target->hasRole('owner') && ! in_array('owner', $newRoles, true) && $this->activeOwners() <= 1) {
            throw ValidationException::withMessages(['roles' => 'The last owner cannot lose the owner role.']);
        }
    }

    private function activeOwners(): int
    {
        return User::role('owner')->where('status', User::STATUS_ACTIVE)->count();
    }

    private function assignableRoles(): array
    {
        return array_values(array_diff(array_keys(config('rbac.roles')), ['practitioner']));
    }
}
