<?php

namespace App\Domain\Payments;

use App\Domain\Booking\Slots;
use App\Domain\Palmistry\PalmReadings;
use App\Domain\Puja\PujaBookings;
use App\Domain\Reports\ReportJobs;
use App\Models\BirthProfile;
use App\Models\OfferingVariant;
use App\Models\User;
use App\Support\Uploads\SafeImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Details collected BEFORE payment (owner decision, 5 Nov 2026): palm photos, puja sankalp,
 * birth details for reports, the question for a written answer, and the address for a site
 * visit. They travel in the order snapshot (personal parts encrypted) and become the palm
 * reading, puja booking or report job as soon as the payment is captured, so nothing is left
 * to do after paying. Consultations already pick the time and birth profile before paying.
 */
class ServiceDetails
{
    public const DISK = 'local';

    /** What has to be collected before paying for this option, or null for nothing. */
    public static function kind(OfferingVariant $v): ?string
    {
        $o = $v->offering;
        $family = $o?->family?->code;
        if (PalmReadings::isPalmOrder(['offering' => ['slug' => $o?->slug], 'variant' => ['mode' => $v->mode]])) {
            return 'palm';
        }
        if (Slots::bookable($v)) {
            return null; // calendar page collects the time, birth profile and topic
        }

        return match (true) {
            in_array($family, ['puja', 'chadhava'], true) => 'puja',
            $family === 'written_question' => 'question',
            in_array($family, ['report', 'bundle'], true) => 'report',
            $v->mode === 'onsite' => 'visit',
            default => null,
        };
    }

    /** Validation rules for the details of a kind. */
    public static function rules(string $kind, OfferingVariant $v): array
    {
        $phone = ['required', 'string', 'regex:/^(?:\+?91|0)?[6-9]\d{9}$|^\+[1-9]\d{7,14}$/'];

        return match ($kind) {
            'palm' => [
                'left' => ['required', 'file', 'max:8192'], 'right' => ['required', 'file', 'max:8192'],
                'dominant_hand' => ['required', Rule::in(['left', 'right'])], 'age' => ['nullable', 'integer', 'min:1', 'max:120'],
                'gender' => ['nullable', Rule::in(['male', 'female', 'other'])], 'language' => ['nullable', Rule::in(['en', 'hi'])],
                'question' => ['nullable', 'string', 'max:1000'], 'consent' => ['accepted'],
            ],
            'puja' => PujaBookings::sankalpRules(),
            'report', 'question' => [
                'profile_a' => ['required', 'string', 'size:26'],
                'profile_b' => [ReportJobs::needsTwo((string) $v->offering?->slug) ? 'required' : 'nullable', 'string', 'size:26', 'different:profile_a'],
                'language' => ['required', Rule::in(['en', 'hi'])],
                'question' => [$kind === 'question' ? 'required' : 'nullable', 'string', 'min:'.($kind === 'question' ? 5 : 0), 'max:2000'],
            ],
            'visit' => [
                'address' => [$v->mode === 'onsite' ? 'required' : 'nullable', 'string', 'max:400'], 'city' => [$v->mode === 'onsite' ? 'required' : 'nullable', 'string', 'max:80'],
                'preferred_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'], 'phone' => $phone,
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
        };
    }

    public static function messages(): array
    {
        return ['consent.accepted' => 'Please agree to the use of your palm photos for this report.', 'phone.regex' => 'Enter a valid mobile number.',
            'profile_a.required' => 'Choose the birth details for this report.', 'question.required' => 'Write your question.'];
    }

    /**
     * Checks the validated details and turns them into the snapshot part of the order.
     * Palm photos are stored privately (re-encoded, EXIF removed) under palm/pending/.
     *
     * @param  array<string, mixed>  $d  validated input
     * @param  array<string, UploadedFile|null>  $files
     * @return array{details: array<string, mixed>}
     */
    public static function prepare(string $kind, User $u, OfferingVariant $v, array $d, array $files = []): array
    {
        $details = ['kind' => $kind];
        switch ($kind) {
            case 'palm':
                if (empty($files['left']) || empty($files['right'])) {
                    throw ValidationException::withMessages(['left' => 'Please upload clear photos of both palms.']);
                }
                $dir = 'palm/pending/'.Str::ulid();
                $details['palm'] = [
                    'left_photo' => SafeImage::store($files['left'], self::DISK, $dir, 'left'), 'right_photo' => SafeImage::store($files['right'], self::DISK, $dir, 'right'),
                    'dominant_hand' => $d['dominant_hand'], 'age' => $d['age'] ?? null, 'gender' => $d['gender'] ?? null,
                    'question' => $d['question'] ?? null, 'report_language' => $d['language'] ?? 'en',
                ];
                break;
            case 'puja':
                $details['puja'] = [
                    'sankalp' => Crypt::encryptString(json_encode(['devotees' => array_values(array_map(fn ($x) => ['name' => trim($x['name']), 'relation' => $x['relation'] ?? null], $d['devotees'])),
                        'gotra' => $d['gotra'] ?? null, 'wish' => $d['wish'] ?? null, 'address' => $d['address'] ?? null, 'phone' => $d['phone']], JSON_UNESCAPED_UNICODE)),
                    'location' => $d['location'], 'preferred_date' => $d['preferred_date'] ?? null,
                ];
                break;
            case 'report':
            case 'question':
                $refs = array_values(array_filter([$d['profile_a'], ReportJobs::needsTwo((string) $v->offering?->slug) ? ($d['profile_b'] ?? null) : null]));
                foreach ($refs as $ref) {
                    $p = BirthProfile::where('user_id', $u->id)->where('ref', $ref)->first();
                    if (! $p) {
                        throw ValidationException::withMessages(['profile_a' => 'Choose your saved birth profile.']);
                    }
                    if (! $p->birth_utc) {
                        throw ValidationException::withMessages(['profile_a' => $p->name.': a birth time is needed for this report. Edit the profile and add the time.']);
                    }
                }
                $details['report'] = ['profiles' => $refs, 'language' => $d['language'], 'question' => $d['question'] ?? null];
                break;
            case 'visit':
                $details['visit'] = Crypt::encryptString(json_encode(['address' => $d['address'] ?? null, 'city' => $d['city'] ?? null,
                    'preferred_date' => $d['preferred_date'] ?? null, 'phone' => $d['phone'], 'notes' => $d['notes'] ?? null], JSON_UNESCAPED_UNICODE));
                break;
        }

        return ['details' => $details];
    }

    /** Readable visit details for staff. */
    public static function visit(array $snap): ?array
    {
        $enc = $snap['details']['visit'] ?? null;

        return $enc ? json_decode(Crypt::decryptString($enc), true) : null;
    }

    /** Deletes palm photos of orders that were never paid (after two days). */
    public static function purgeUnpaid(): int
    {
        $n = 0;
        $orders = DB::table('orders')->whereIn('status', ['pending_payment', 'cancelled', 'failed'])
            ->where('created_at', '<', now()->subDays(2))->where('snapshot', 'like', '%"left_photo"%')->limit(200)->get(['id', 'snapshot']);
        foreach ($orders as $o) {
            $snap = json_decode($o->snapshot, true);
            $p = $snap['details']['palm'] ?? null;
            if (! $p) {
                continue;
            }
            Storage::disk(self::DISK)->delete(array_filter([$p['left_photo'] ?? null, $p['right_photo'] ?? null]));
            unset($snap['details']['palm']['left_photo'], $snap['details']['palm']['right_photo']);
            DB::table('orders')->where('id', $o->id)->update(['snapshot' => json_encode($snap, JSON_UNESCAPED_UNICODE)]);
            $n++;
        }

        return $n;
    }
}
