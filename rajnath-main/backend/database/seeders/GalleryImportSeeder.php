<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Publishes the albums made from the owner's Facebook photos (6 Nov 2026) and puts real photos on
 * a few service pages that still show an illustration. Runs once; albums the owner edits, hides
 * or deletes in Admin › Gallery stay as the owner left them.
 */
class GalleryImportSeeder extends Seeder
{
    public const FLAG = 'gallery.facebook_imported_2026_11_06';

    /** Service slug => [album slug, photo number] used as the service photo. */
    public const SERVICE_PHOTOS = [
        'puja-rituals' => ['sharadiya-navratri-anushthan-2026', 18],
        'durga-saptashati-path' => ['sharadiya-navratri-anushthan-2026', 2],
        'durga-ashtami-puja' => ['sharadiya-navratri-anushthan-2026', 1],
        'janma-kundali-reading' => ['at-the-office-and-portraits', 18],
    ];

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $albums = require database_path('seeders/data/gallery_albums.php');
        $sort = 10;
        foreach ($albums as $a) {
            if (DB::table('gallery_albums')->where('slug', $a['slug'])->exists()) {
                continue;
            }
            $id = DB::table('gallery_albums')->insertGetId([
                'ref' => (string) Str::ulid(), 'slug' => $a['slug'], 'title' => $a['title'], 'title_hi' => $a['title_hi'], 'description' => $a['description'],
                'category' => $a['category'], 'event_date' => $a['date'], 'location' => $a['location'], 'published' => true, 'featured' => $a['featured'],
                'sort' => $sort++, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($a['items'] as $i => $p) {
                DB::table('gallery_items')->insert(['album_id' => $id, 'kind' => 'photo', 'path' => $p['path'], 'thumb_path' => $p['thumb'], 'width' => $p['w'], 'height' => $p['h'],
                    'sort' => $i + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        foreach (self::SERVICE_PHOTOS as $slug => [$album, $n]) {
            $path = sprintf('images/gallery/%s/%02d.jpg', $album, $n);
            if (is_file(public_path($path))) {
                DB::table('offerings')->where('slug', $slug)->where(fn ($q) => $q->whereNull('image_path')->orWhere('image_path', 'like', 'images/art/%'))
                    ->update(['image_path' => $path, 'updated_at' => now()]);
            }
        }
        $settings->set(self::FLAG, now()->toDateString());
    }
}
