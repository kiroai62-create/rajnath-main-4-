<?php

namespace App\Domain\Notify;

use App\Models\User;
use App\Support\Settings\SettingsRepository;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails the team when new work arrives (paid order, enquiry, palm photos, sankalp, report
 * details, support request), so nothing waits unseen in an admin queue. Recipients are the
 * active staff holding the permission for that work, the owners, and the optional address in
 * Admin › Settings (notify.staff_email, default: the contact email).
 */
class StaffAlerts
{
    public function __construct(private SettingsRepository $settings) {}

    public function send(string $permission, string $subject, string $body, ?string $adminUrl = null, string $prefix = '[New] '): void
    {
        if (! $this->settings->get('notify.staff_alerts', true)) {
            return;
        }
        $to = $this->recipients($permission);
        if ($to === []) {
            return;
        }
        $text = $body.($adminUrl ? "\n\nOpen in admin: $adminUrl" : '')."\n\n— ".(config('brand')['brand.name'] ?? 'Astro Rajnath Jha');
        $subject = $prefix.$subject;
        dispatch(function () use ($to, $subject, $text) {
            try {
                Mail::raw($text, fn ($m) => $m->to($to)->subject($subject));
            } catch (Throwable $e) {
                report($e);
            }
        })->afterCommit();
    }

    /** @return array<int, string> */
    public function recipients(string $permission): array
    {
        $staff = User::query()->where('kind', User::KIND_STAFF)->where('status', User::STATUS_ACTIVE)->whereHas('roles')->get()
            ->filter(fn (User $u) => $u->can($permission))
            ->pluck('email');
        $extra = (string) ($this->settings->get('notify.staff_email') ?: $this->settings->get('contact.email', config('brand')['contact.email'] ?? ''));

        return $staff->push($extra)->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->map(fn ($e) => mb_strtolower($e))->unique()->values()->all();
    }
}
