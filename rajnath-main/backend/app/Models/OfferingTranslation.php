<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferingTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['locale', 'title', 'summary', 'body', 'includes', 'audience', 'process', 'benefits', 'faqs', 'exclusions', 'disclaimer', 'meta_description'];

    protected function casts(): array
    {
        return ['includes' => 'array', 'audience' => 'array', 'process' => 'array', 'benefits' => 'array', 'faqs' => 'array'];
    }

    /**
     * Service-specific questions with answers.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function faqList(): array
    {
        return collect($this->faqs ?? [])
            ->filter(fn ($f) => is_array($f) && filled($f[0] ?? null) && filled($f[1] ?? null))
            ->map(fn ($f) => [(string) $f[0], (string) $f[1]])->values()->all();
    }
}
