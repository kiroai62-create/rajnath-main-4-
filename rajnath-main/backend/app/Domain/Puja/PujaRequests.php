<?php

namespace App\Domain\Puja;

use App\Domain\Notify\Notifier;
use App\Domain\Notify\StaffAlerts;
use App\Models\PujaRequest;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Settings\SettingsRepository;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Puja on request. Online puja and chadhava are no longer sold on the website: a visitor fills
 * in one form with everything the team needs, and staff call back to fix the date, place and
 * dakshina in Admin › Puja requests. The form's puja list and intro text are owner-editable.
 */
class PujaRequests
{
    /** Service families that are no longer sold online (their old pages redirect to the form). */
    public const RETIRED_FAMILIES = ['puja', 'chadhava'];

    public function __construct(private SettingsRepository $settings, private AuditLogger $audit) {}

    /** @return list<string> */
    public function types(): array
    {
        $saved = $this->settings->get('puja_request.types');
        $list = is_array($saved) && $saved !== [] ? $saved : PujaRequest::DEFAULT_TYPES;

        return array_values(array_unique(array_filter(array_map(fn ($t) => trim((string) $t), $list))));
    }

    public function intro(): string
    {
        return (string) ($this->settings->get('puja_request.intro') ?: 'Tell us which puja you need and why. Acharya Ji\'s team will call you within one working day to suggest the right vidhi, fix a shubh muhurat and confirm the dakshina. Nothing is charged online.');
    }

    public function open(): bool
    {
        return (bool) $this->settings->get('puja_request.open', true);
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $today = now('Asia/Kolkata')->toDateString();

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:190'],
            'puja_type' => ['required', 'string', 'max:150'],
            'puja_other' => ['nullable', 'required_if:puja_type,other', 'string', 'max:150'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'mode' => ['required', Rule::in(array_keys(PujaRequest::MODES))],
            'address' => ['nullable', 'required_if:mode,home', 'string', 'max:300'],
            'preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'alternate_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$today],
            'preferred_time' => ['nullable', Rule::in(array_keys(PujaRequest::TIMES))],
            'participants' => ['nullable', 'string', 'max:500'],
            'gotra' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.$today],
            'birth_time' => ['nullable', 'date_format:H:i'],
            'birth_place' => ['nullable', 'string', 'max:200'],
            'language' => ['nullable', Rule::in(PujaRequest::LANGUAGES)],
            'samagri' => ['nullable', Rule::in(array_keys(PujaRequest::SAMAGRI))],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ];
    }

    /** Indian mobile or any E.164 number, normalised; null when it is not a phone number. */
    public static function phone(string $raw): ?string
    {
        $digits = preg_replace('/[\s\-()]/', '', $raw);
        if (preg_match('/^(?:\+?91|0)?([6-9]\d{9})$/', $digits, $m)) {
            return '+91'.$m[1];
        }

        return preg_match('/^\+[1-9]\d{7,14}$/', $digits) ? $digits : null;
    }

    /**
     * @param  array<string, mixed>  $data  validated input
     */
    public function create(array $data, string $phone, ?User $user, ?string $ip): PujaRequest
    {
        $type = $data['puja_type'] === 'other' ? trim((string) $data['puja_other']) : $data['puja_type'];
        $r = PujaRequest::create([
            'user_id' => $user && ! $user->isStaff() ? $user->id : null,
            'name' => $data['name'], 'phone_e164' => $phone, 'email' => $data['email'] ?? null,
            'puja_type' => $type, 'purpose' => $data['purpose'] ?? null, 'mode' => $data['mode'],
            'address' => $data['mode'] === 'home' ? ($data['address'] ?? null) : null,
            'preferred_date' => $data['preferred_date'] ?? null, 'alternate_date' => $data['alternate_date'] ?? null,
            'preferred_time' => $data['preferred_time'] ?? null, 'participants' => $data['participants'] ?? null,
            'gotra' => $data['gotra'] ?? null, 'birth_date' => $data['birth_date'] ?? null, 'birth_time' => $data['birth_time'] ?? null,
            'birth_place' => $data['birth_place'] ?? null, 'language' => $data['language'] ?? null,
            'samagri' => $data['samagri'] ?? null, 'message' => $data['message'] ?? null, 'ip' => $ip,
        ]);
        $this->audit->record('puja_request.created', $r, ['puja' => $type], $user, $user ? null : 'guest');
        app(StaffAlerts::class)->send('bookings.view', 'Puja request: '.$type, $data['name'].' ('.$phone.') asked for '.$type
            .' ('.(PujaRequest::MODES[$data['mode']] ?? $data['mode']).')'.(! empty($data['preferred_date']) ? ', preferred '.$data['preferred_date'] : '').'. '
            .mb_substr((string) ($data['purpose'] ?? ''), 0, 300), route('admin.puja-requests.show', $r));
        if ($r->user_id) {
            app(Notifier::class)->send($r->user_id, 'puja', 'Puja request received', 'Thank you. Our team will call or WhatsApp you on '.$phone.' within one working day to fix the date for '.$type.'.', $r->ref, route('account.puja.index'));
        }

        return $r;
    }

    /**
     * Staff follow-up: status, the agreed date and time (India time), priest, dakshina and notes.
     *
     * @param  array<string, mixed>  $data  validated input
     */
    public function update(PujaRequest $r, array $data, User $staff): void
    {
        $before = ['status' => $r->status, 'scheduled_at' => $r->scheduled_at?->toIso8601String()];
        $at = ! empty($data['scheduled_date'])
            ? Carbon::parse($data['scheduled_date'].' '.(($data['scheduled_time'] ?? null) ?: '09:00'), 'Asia/Kolkata')->utc()
            : null;
        $r->forceFill([
            'status' => $data['status'], 'scheduled_at' => $at, 'priest' => $data['priest'] ?? null,
            'quoted_minor' => isset($data['quoted']) && $data['quoted'] !== '' ? (int) round(((float) $data['quoted']) * 100) : null,
            'meeting_link' => $data['meeting_link'] ?? null, 'staff_note' => $data['staff_note'] ?? null, 'handled_by' => $staff->id,
        ])->save();
        $this->audit->record('puja_request.updated', $r, ['from' => $before, 'to' => ['status' => $r->status, 'scheduled_at' => $r->scheduled_at?->toIso8601String()]], $staff);

        $dateChanged = $before['scheduled_at'] !== $r->scheduled_at?->toIso8601String();
        if ($r->user_id && $r->status === 'scheduled' && ($dateChanged || $before['status'] !== 'scheduled') && $r->scheduled_at) {
            app(Notifier::class)->send($r->user_id, 'puja', 'Puja date fixed', $r->puja_type.' is fixed for '.$r->scheduled_at->tz('Asia/Kolkata')->format('d M Y, g:i A').' (India time).'
                .($r->meeting_link ? ' Join link: '.$r->meeting_link : ''), $r->ref, route('account.puja.index'));
        }
    }
}
