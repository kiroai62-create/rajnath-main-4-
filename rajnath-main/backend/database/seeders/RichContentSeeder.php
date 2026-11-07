<?php

namespace Database\Seeders;

use App\Models\Offering;
use Illuminate\Database\Seeder;

/**
 * Detailed service pages (Nov 2026): longer description, step-by-step vidhi, benefits and FAQs.
 * English text is replaced only while it is still a seeded text (never edited by the owner);
 * the new procedure, benefits and FAQ fields are filled only while empty.
 */
class RichContentSeeder extends Seeder
{
    private const TEXT_FIELDS = ['summary', 'body', 'includes', 'audience', 'disclaimer'];

    public function run(): void
    {
        $content = self::content();
        $seeded = array_flip(require database_path('seeders/data/seeded_service_text.php'));
        foreach ($content as $slug => $c) {
            $o = Offering::where('slug', $slug)->first();
            $t = $o?->translations()->where('locale', 'en')->first();
            if (! $t) {
                continue;
            }
            $untouched = $t->body === null || $t->body === '' || isset($seeded[md5($t->body)])
                || str_ends_with((string) $t->summary, '— details to be finalised before publication.');
            if ($untouched) {
                foreach (self::TEXT_FIELDS as $f) {
                    if (isset($c[$f])) {
                        $t->{$f} = $c[$f];
                    }
                }
                if (isset($c['summary'])) {
                    $t->meta_description = mb_substr($c['summary'], 0, 300);
                }
            }
            foreach (['process', 'benefits', 'faqs'] as $f) {
                if (empty($t->{$f}) && ! empty($c[$f])) {
                    $t->{$f} = $c[$f];
                }
            }
            $t->save();
            if (! $o->image_path && is_file(public_path("images/art/$slug.jpg"))) {
                $o->forceFill(['image_path' => "images/art/$slug.jpg"])->save();
            }
        }
    }

    /** @return array<string, array<string, mixed>> */
    public static function content(): array
    {
        $dir = database_path('seeders/data');

        $all = array_merge(
            require "$dir/service_content_planets.php",
            require "$dir/service_content_detailed.php",
            require "$dir/service_content_pujas.php",
            require "$dir/service_content_reports.php",
        );
        foreach (require "$dir/service_content_more.php" as $slug => $more) {
            $all[$slug] = array_replace($all[$slug] ?? [], $more);
        }

        return $all;
    }
}
