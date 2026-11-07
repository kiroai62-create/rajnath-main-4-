<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Owner decision (5 Nov 2026): open the store with the full starter list at market rates.
 * Runs once. Products the owner already edited in Admin › Store are left exactly as they are;
 * the rest go on sale with a starting stock that the owner should check and correct.
 */
class StoreLaunchSeeder extends Seeder
{
    public const FLAG = 'store.launched_2026_11_05';

    /** Starting stock per category. */
    public const STOCK = ['gemstone' => 3, 'rudraksha' => 5, 'idol' => 5, 'vastu' => 10, 'yantra' => 10, 'mala' => 10, 'kit' => 10, 'samagri' => 25];

    /** Details shown for each gemstone: [weight, origin, certificate]. */
    public const GEMS = [
        'ruby-manik' => ['5.25 carat (about 5.8 ratti)', 'Mozambique / Burma', 'Lab certificate (IGI / GII / GRS)'],
        'pearl-moti' => ['5.25 carat (about 5.8 ratti)', 'Basra / South Sea natural pearl', 'Lab certificate for natural pearl'],
        'red-coral-moonga' => ['6.25 carat (about 7 ratti)', 'Italian', 'Lab certificate'],
        'emerald-panna' => ['4.25 carat (about 4.7 ratti)', 'Colombia / Zambia', 'Lab certificate (IGI / GII)'],
        'yellow-sapphire-pukhraj' => ['5.25 carat (about 5.8 ratti)', 'Ceylon (Sri Lanka)', 'Lab certificate (IGI / GII / GRS), unheated where stated'],
        'diamond-heera' => ['0.25 carat, VS clarity', 'Natural', 'IGI / GIA certificate'],
        'blue-sapphire-neelam' => ['4.25 carat (about 4.7 ratti)', 'Ceylon (Sri Lanka)', 'Lab certificate (IGI / GII / GRS)'],
        'hessonite-gomed' => ['6.25 carat (about 7 ratti)', 'Ceylon (Sri Lanka)', 'Lab certificate'],
        'cats-eye-lehsunia' => ['5.25 carat (about 5.8 ratti)', 'Ceylon (Sri Lanka) / India', 'Lab certificate'],
        'opal' => ['5.25 carat', 'Australia', 'Lab certificate'],
        'citrine-sunela' => ['5.25 carat', 'Brazil', 'Lab certificate'],
        'amethyst-katela' => ['5.25 carat', 'Brazil / Zambia', 'Lab certificate'],
        'peridot' => ['5.25 carat', 'Pakistan / Arizona', 'Lab certificate'],
        'moonstone' => ['5.25 carat', 'Ceylon (Sri Lanka)', 'Lab certificate'],
        'garnet-tamra' => ['5.25 carat', 'Africa / India', 'Lab certificate'],
    ];

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $this->call(StoreSeeder::class);
        foreach (DB::table('products')->get() as $p) {
            $untouched = ! $p->active && (int) $p->stock === 0 && (string) $p->updated_at === (string) $p->created_at;
            if (! $untouched) {
                continue;
            }
            $gem = self::GEMS[$p->slug] ?? null;
            DB::table('products')->where('id', $p->id)->update(array_filter([
                'active' => true, 'stock' => self::STOCK[$p->category] ?? 10,
                'weight' => $gem[0] ?? $p->weight, 'origin' => $gem[1] ?? $p->origin, 'certification' => $gem[2] ?? $p->certification,
                'ship_weight_g' => match ($p->category) {
                    'gemstone', 'rudraksha' => 100, 'idol', 'kit', 'vastu' => 800, default => 250
                },
                'description' => $p->description."\n\n".($p->category === 'gemstone'
                    ? 'Price is for the weight shown; other weights are available on request. Delivered in a sealed pouch with the lab certificate. Shipping across India is included and tracked.'
                    : 'Packed carefully and shipped across India with tracking. Shipping is included in the price.'),
                'updated_at' => now(),
            ], fn ($v) => $v !== null));
        }
        $settings->set(self::FLAG, now()->toDateString());
    }
}
