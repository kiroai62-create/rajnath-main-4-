<?php

namespace App\Models;

use Database\Factories\ToolRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** One free-calculator run (Kundli, numerology …). The entered details are encrypted at rest. */
class ToolRun extends Model
{
    /** @use HasFactory<ToolRunFactory> */
    use HasFactory;

    protected $fillable = ['tool', 'channel', 'user_id', 'visitor', 'inputs', 'summary', 'country', 'device', 'locale'];

    protected static function booted(): void
    {
        static::creating(fn (ToolRun $r) => $r->ref ??= (string) Str::ulid());
    }

    protected function casts(): array
    {
        return ['inputs' => 'encrypted:array', 'last_pdf_at' => 'datetime', 'pdf_downloads' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'ref';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Name and birth details in one line, for lists. */
    public function personLine(): string
    {
        $in = $this->inputs ?? [];
        $parts = [];
        foreach (['', 'a_', 'b_'] as $p) {
            $bits = array_filter([$in[$p.'name'] ?? null, $in[$p.'birth_date'] ?? null, $in[$p.'birth_time'] ?? null, $in[$p.'birth_place'] ?? null]);
            if ($bits) {
                $parts[] = implode(', ', $bits);
            }
        }
        if (isset($in['partner'])) {
            $parts[] = 'with '.$in['partner'];
        }
        if (isset($in['plate'])) {
            $parts[] = $in['plate'];
        }

        return implode(' · ', $parts) ?: '—';
    }
}
