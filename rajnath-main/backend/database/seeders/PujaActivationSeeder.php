<?php

namespace Database\Seeders;

use App\Support\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owner decision (Nov 2026): pujas and chadhavas are performed by Acharya Dr. Rajnath Jha and
 * the Jyotirveda Vigyan Sansthan team. This adds that in-house priest record, links it to every
 * puja and chadhava, and puts the priced ones on sale. Runs once: anything the owner switches
 * off or relinks later stays as the owner left it. Outside priests or temples can still be added
 * in Admin › Priests & temples.
 */
class PujaActivationSeeder extends Seeder
{
    public const FLAG = 'catalogue.puja_in_house_activated';

    public function run(): void
    {
        $settings = app(SettingsRepository::class);
        if ($settings->get(self::FLAG)) {
            return;
        }
        $supplier = DB::table('suppliers')->where('authorization_doc', 'in-house')->first();
        $id = $supplier->id ?? DB::table('suppliers')->insertGetId([
            'ref' => (string) Str::ulid(), 'kind' => 'priest', 'name' => 'Acharya Dr. Rajnath Jha — Jyotirveda Vigyan Sansthan (in-house)',
            'contact_name' => 'Acharya Dr. Rajnath Jha', 'city' => 'Patna',
            'notes' => 'In-house: pujas performed by Acharya Ji and his team; chadhavas offered by the team at the temple named in the booking.',
            'authorization_doc' => 'in-house', 'verified_at' => now(), 'active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $offerings = DB::table('offerings as o')->join('service_families as f', 'f.id', '=', 'o.family_id')->whereIn('f.code', ['puja', 'chadhava'])->pluck('o.id');
        foreach ($offerings as $oid) {
            DB::table('offering_supplier')->insertOrIgnore(['offering_id' => $oid, 'supplier_id' => $id]);
            $priced = DB::table('offering_variants')->where('offering_id', $oid)->where('active', true)->whereNotNull('price_minor')->exists();
            if ($priced) {
                DB::table('offerings')->where('id', $oid)->where('status', 'draft')->update(['status' => 'active', 'activated_at' => now(), 'updated_at' => now()]);
            }
        }
        $settings->set(self::FLAG, now()->toDateString());
    }
}
