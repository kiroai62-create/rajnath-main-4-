<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** A visitor's request for a puja; the team calls back and fixes the date, place and dakshina. */
class PujaRequest extends Model
{
    public const STATUSES = [
        'new' => 'New', 'contacted' => 'Contacted', 'scheduled' => 'Date fixed', 'completed' => 'Completed',
        'cancelled' => 'Cancelled', 'spam' => 'Spam',
    ];

    public const MODES = [
        'online' => 'Online (live video, in my name)', 'home' => 'At my home or office', 'temple' => 'At a temple', 'not_sure' => 'Not sure, please advise',
    ];

    public const TIMES = [
        'muhurat' => 'Best muhurat (let Acharya Ji choose)', 'morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening',
    ];

    public const SAMAGRI = [
        'we_arrange' => 'Please arrange the samagri', 'i_arrange' => 'I will arrange it myself', 'not_sure' => 'Not sure',
    ];

    public const LANGUAGES = ['Hindi', 'English', 'Maithili', 'Bhojpuri', 'Bengali', 'Marathi', 'Gujarati', 'Tamil', 'Telugu', 'Kannada'];

    /** Puja types offered in the form until the owner edits the list in Admin › Puja requests. */
    public const DEFAULT_TYPES = [
        'Satyanarayan Katha', 'Rudrabhishek', 'Maha Mrityunjaya Jaap', 'Navagraha Shanti', 'Kaal Sarp Dosh Nivaran',
        'Mangal Dosh Shanti', 'Pitra Dosh Shanti', 'Shani Shanti', 'Rahu–Ketu Shanti', 'Griha Pravesh',
        'Vastu Shanti', 'Lakshmi Puja', 'Ganesh Puja', 'Durga Saptashati Path', 'Sundarkand Path',
        'Baglamukhi Puja', 'Santan Prapti Puja', 'Vivah (marriage) Puja', 'Namkaran / Mundan Sanskar', 'Shraddh / Pind Daan',
    ];

    protected $fillable = [
        'user_id', 'name', 'phone_e164', 'email', 'puja_type', 'purpose', 'mode', 'address', 'preferred_date', 'alternate_date',
        'preferred_time', 'participants', 'gotra', 'birth_date', 'birth_time', 'birth_place', 'language', 'samagri', 'message', 'ip',
    ];

    protected static function booted(): void
    {
        static::creating(fn (PujaRequest $r) => $r->ref ??= (string) Str::ulid());
    }

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date', 'alternate_date' => 'date', 'birth_date' => 'date', 'scheduled_at' => 'datetime',
            'quoted_minor' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'ref';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
