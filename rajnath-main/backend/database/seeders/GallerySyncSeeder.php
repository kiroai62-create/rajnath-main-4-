<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time gallery top-up (7 Nov 2026): every bundled photo appears in the public gallery.
 * Albums that are missing are created; albums that exist get the photos they are missing (matched
 * by file path); the owner portraits that were only used on the About page get their own album.
 * Runs once (flag below), so later edits and deletions in Admin › Gallery are kept.
 */
class GallerySyncSeeder extends Seeder
{
    public const FLAG = 'gallery.synced_2026_11_07';

    /** Owner albums: [slug, title, category, description, [file, thumb|null]]. */
    private const OWNER_ALBUMS = [
        ['awards-and-honours', 'Awards and honours', 'awards', 'Trophies, certificates and honours received in India and abroad.',
            [['award-malaysia-2025-480.jpg', null], ['awards-wall-wide-1200.jpg', 'awards-wall-wide-480.jpg']]],
        ['at-the-office-in-kankarbagh-patna', 'At the office in Kankarbagh, Patna', 'office', 'Where Acharya Ji meets visitors for consultations.',
            [['office-wide-960.jpg', 'office-wide-480.jpg'], ['desk-black-480.jpg', null], ['writing-navy-480.jpg', null], ['writing-red-480.jpg', null], ['seated-navy-480.jpg', null], ['writing-red-desk-480.jpg', null]]],
        ['portraits-of-acharya-ji', 'Portraits of Acharya Ji', 'office', 'Portraits of Acharya Dr. Rajnath Jha at his office in Patna.',
            [['portrait-navy-960.jpg', 'portrait-navy-480.jpg'], ['portrait-red-480.jpg', null]]],
    ];

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $sort = (int) DB::table('gallery_albums')->max('sort') + 10;
        foreach (require database_path('seeders/data/gallery_albums.php') as $a) {
            $items = array_map(fn ($p) => ['path' => $p['path'], 'thumb' => $p['thumb'], 'w' => $p['w'], 'h' => $p['h']], $a['items']);
            $this->sync($a['slug'], ['title' => $a['title'], 'title_hi' => $a['title_hi'], 'description' => $a['description'], 'category' => $a['category'],
                'event_date' => $a['date'], 'location' => $a['location'], 'featured' => $a['featured']], $items, $sort);
        }
        foreach (self::OWNER_ALBUMS as [$slug, $title, $category, $description, $files]) {
            $items = [];
            foreach ($files as [$file, $thumb]) {
                $path = 'images/owner/'.$file;
                if (! is_file(public_path($path))) {
                    continue;
                }
                [$w, $h] = getimagesize(public_path($path)) ?: [null, null];
                $items[] = ['path' => $path, 'thumb' => $thumb && is_file(public_path('images/owner/'.$thumb)) ? 'images/owner/'.$thumb : $path, 'w' => $w, 'h' => $h];
            }
            $this->sync($slug, ['title' => $title, 'title_hi' => null, 'description' => $description, 'category' => $category, 'event_date' => null, 'location' => 'Patna', 'featured' => true], $items, $sort);
        }
        $settings->set(self::FLAG, now()->toDateString());
    }

    private function sync(string $slug, array $album, array $items, int &$sort): void
    {
        $row = DB::table('gallery_albums')->where('slug', $slug)->first();
        $id = $row?->id ?? DB::table('gallery_albums')->insertGetId([
            'ref' => (string) Str::ulid(), 'slug' => $slug, 'title' => $album['title'], 'title_hi' => $album['title_hi'], 'description' => $album['description'],
            'category' => $album['category'], 'event_date' => $album['event_date'], 'location' => $album['location'], 'published' => true, 'featured' => $album['featured'],
            'sort' => $sort += 10, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $have = DB::table('gallery_items')->where('album_id', $id)->pluck('path')->all();
        $next = (int) DB::table('gallery_items')->where('album_id', $id)->max('sort');
        foreach ($items as $p) {
            if (in_array($p['path'], $have, true) || ! is_file(public_path($p['path']))) {
                continue;
            }
            DB::table('gallery_items')->insert(['album_id' => $id, 'kind' => 'photo', 'path' => $p['path'], 'thumb_path' => $p['thumb'], 'width' => $p['w'], 'height' => $p['h'],
                'sort' => ++$next, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
