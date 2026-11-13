<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the first admin (owner role, full access) when the site has no owner yet. Runs on
 * every deploy (deploy/deploy.sh) and does nothing once an owner exists, so it never changes an
 * existing password.
 *
 *   php artisan arj:ensure-admin                 # uses ADMIN_EMAIL / ADMIN_NAME / ADMIN_PASSWORD
 *   php artisan arj:ensure-admin --email=me@example.com --password='…'
 *   php artisan arj:ensure-admin --reset --email=me@example.com    # forgotten password: new one printed
 *
 * With no password given, a random one is printed once; the admin is asked to change it after
 * signing in (Admin › My account).
 */
class EnsureAdmin extends Command
{
    protected $signature = 'arj:ensure-admin {--email= : Admin email (default ADMIN_EMAIL, else admin@<your domain>)} {--name= : Admin name} {--password= : Password (default ADMIN_PASSWORD, else random)} {--reset : Give an existing staff account a new password and turn its 2FA off}';

    protected $description = 'Create the first admin automatically (or reset a forgotten admin password).';

    public function handle(): int
    {
        $this->callSilently('arj:rbac-sync');
        $cfg = config('rbac.bootstrap_admin');
        $email = strtolower(trim((string) ($this->option('email') ?: ($cfg['email'] ?? '') ?: 'admin@'.$this->domain())));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid admin email: '.$email);

            return self::FAILURE;
        }
        $given = (string) ($this->option('password') ?: ($cfg['password'] ?? ''));
        $password = $given !== '' ? $given : self::randomPassword();
        if (mb_strlen($password) < 12) {
            $this->error('The admin password must be at least 12 characters.');

            return self::FAILURE;
        }

        if ($this->option('reset')) {
            return $this->reset($email, $password, $given === '');
        }

        $owner = User::role('owner')->where('status', User::STATUS_ACTIVE)->first();
        if ($owner) {
            $this->line('Admin already exists ('.$owner->email.'). Nothing changed. Sign in at '.url('/staff/login'));

            return self::SUCCESS;
        }

        DB::transaction(function () use ($email, $password, $given, $cfg) {
            $user = User::where('email', $email)->lockForUpdate()->first() ?? new User(['email' => $email]);
            $user->name = (string) ($this->option('name') ?: ($user->name ?: ($cfg['name'] ?? 'Administrator')));
            $user->kind = User::KIND_STAFF;
            $user->status = User::STATUS_ACTIVE;
            $user->password = $password;
            $user->password_changed_at = now();
            $user->password_must_change = $given === '';
            $user->email_verified_at ??= now();
            $user->save();
            $user->syncRoles(array_unique([...$user->getRoleNames()->all(), 'owner']));
        });

        $this->newLine();
        $this->info('Admin account created (full access).');
        $this->line('  Sign in:  '.url('/staff/login'));
        $this->line('  Email:    '.$email);
        $this->line('  Password: '.($given === '' ? $password : '(the ADMIN_PASSWORD you set)'));
        if ($given === '') {
            $this->warn('  Save this password now; it is shown only once. Change it in Admin › My account after signing in.');
        }
        $this->newLine();

        return self::SUCCESS;
    }

    private function reset(string $email, string $password, bool $random): int
    {
        $user = User::where('email', $email)->first();
        if (! $user || ! $user->isStaff()) {
            $this->error('No staff account with the email '.$email.'.');

            return self::FAILURE;
        }
        $user->forceFill([
            'password' => $password, 'password_changed_at' => now(), 'password_must_change' => $random,
            'mfa_secret' => null, 'mfa_confirmed_at' => null, 'status' => User::STATUS_ACTIVE,
        ])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->info('New password set for '.$email.' (two-factor sign-in was turned off; turn it on again in Admin › My account).');
        $this->line('  Password: '.($random ? $password : '(the password you gave)'));

        return self::SUCCESS;
    }

    private function domain(): string
    {
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host !== '' && $host !== 'localhost' && str_contains($host, '.') ? preg_replace('/^www\./', '', $host) : 'example.com';
    }

    /** 16 characters with upper and lower case letters, digits and a symbol (meets the staff password rule). */
    public static function randomPassword(): string
    {
        $chars = str_split(Str::password(13, true, true, false).'Aa9');
        $chars[] = ['!', '@', '#', '%', '*', '-', '+', '?'][random_int(0, 7)];
        shuffle($chars);

        return implode('', $chars);
    }
}
