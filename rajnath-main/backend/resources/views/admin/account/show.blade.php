<x-layouts.admin title="My account">
    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.account.profile') }}" class="card space-y-4 p-5 sm:p-6">
            @csrf @method('PUT')
            <h2 class="text-lg">Name and sign-in email</h2>
            <x-field name="name" label="Your name" :value="$user->name" autocomplete="name" required />
            <x-field name="email" label="Sign-in email" type="email" :value="$user->email" autocomplete="username" required />
            <x-field name="current_password" id="p-current" label="Current password" type="password" autocomplete="current-password" required />
            <button class="btn-primary">Save name and email</button>
        </form>

        <form method="POST" action="{{ route('admin.account.password') }}" class="card space-y-4 p-5 sm:p-6">
            @csrf @method('PUT')
            <h2 class="text-lg">Change password</h2>
            @if ($user->password_must_change)
                <p role="alert" class="rounded-xl bg-gold-50 p-3 text-sm ring-1 ring-gold-200">You are using the password created during installation. Choose your own now.</p>
            @endif
            <x-field name="current_password" id="pw-current" label="Current password" type="password" autocomplete="current-password" required />
            <x-field name="password" label="New password" type="password" autocomplete="new-password" required hint="At least 12 characters with upper and lower case letters, a number and a symbol." />
            <x-field name="password_confirmation" label="New password again" type="password" autocomplete="new-password" required />
            <button class="btn-primary">Change password</button>
            @if ($user->password_changed_at)<p class="text-xs text-ink-600">Last changed {{ $user->password_changed_at->tz('Asia/Kolkata')->format('d M Y, g:i A') }} IST.</p>@endif
        </form>

        <section class="card space-y-4 p-5 sm:p-6 lg:col-span-2" aria-labelledby="mfa-h">
            <h2 id="mfa-h" class="text-lg">Two-factor sign-in (authenticator app) <span @class(['badge-success' => $user->hasMfa(), 'badge-gold' => ! $user->hasMfa()])>{{ $user->hasMfa() ? 'On' : 'Off' }}</span></h2>
            @if ($user->hasMfa())
                <p class="text-sm text-ink-600">Every sign-in asks for a 6-digit code from your authenticator app. To turn it off (for example when changing phone), confirm with your password and a current code.</p>
                <form method="POST" action="{{ route('admin.account.mfa.disable') }}" class="grid max-w-xl gap-4 sm:grid-cols-2">
                    @csrf @method('DELETE')
                    <x-field name="current_password" id="mfa-off-pass" label="Current password" type="password" autocomplete="current-password" required />
                    <x-field name="code" id="mfa-off-code" label="Authenticator code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required />
                    <div class="sm:col-span-2"><button class="btn-danger">Turn off two-factor sign-in</button></div>
                </form>
            @else
                <p class="text-sm text-ink-600">Strongly recommended for the admin. Scan the QR code with Google Authenticator, Microsoft Authenticator or 1Password, then enter the code it shows.</p>
                <div class="grid gap-6 sm:grid-cols-[13rem_1fr]">
                    <div>
                        <div class="w-48 rounded-xl bg-white p-2 ring-1 ring-ink-200" role="img" aria-label="QR code for authenticator setup">{!! $qr !!}</div>
                        <details class="mt-2 text-sm"><summary class="cursor-pointer">Can't scan? Enter this key</summary><code class="mt-2 block break-all rounded-lg bg-ivory-100 p-2 text-xs">{{ chunk_split($secret, 4, ' ') }}</code></details>
                    </div>
                    <form method="POST" action="{{ route('admin.account.mfa.enable') }}" class="max-w-sm space-y-4">
                        @csrf
                        <x-field name="code" id="mfa-on-code" label="Code shown in the app" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required />
                        <x-field name="current_password" id="mfa-on-pass" label="Current password" type="password" autocomplete="current-password" required />
                        <button class="btn-primary">Turn on two-factor sign-in</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
