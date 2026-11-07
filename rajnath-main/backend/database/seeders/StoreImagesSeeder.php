<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Swaps the drawn placeholder art (images/art/store-*.jpg) for real product photos bundled in
 * public/images/store/<slug>.jpg (freely licensed, credits in credits.json). A photo the owner
 * uploaded in Admin › Gem store is never replaced. Safe to run on every deploy.
 */
class StoreImagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DB::table('products')->get(['id', 'slug', 'image_path']) as $p) {
            $photo = 'images/store/'.$p->slug.'.jpg';
            $placeholder = $p->image_path === null || str_starts_with((string) $p->image_path, 'images/art/store-');
            if ($placeholder && is_file(public_path($photo))) {
                DB::table('products')->where('id', $p->id)->update(['image_path' => $photo]);
            }
        }
    }
}
