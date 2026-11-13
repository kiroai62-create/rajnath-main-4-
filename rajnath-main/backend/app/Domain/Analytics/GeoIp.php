<?php

namespace App\Domain\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * IP address → country from the free DB-IP "IP to Country Lite" database (CC BY 4.0,
 * https://db-ip.com), kept in the geoip_ranges table and refreshed monthly. Lookups are local,
 * so a visitor's IP address is never sent to another service.
 */
class GeoIp
{
    public const SOURCE = 'https://download.db-ip.com/free/dbip-country-lite-%s.csv.gz';

    /** 16-byte form; IPv4 is stored IPv4-mapped (::ffff:a.b.c.d) so all ranges sort together. */
    public static function pack(string $ip): ?string
    {
        $bin = @inet_pton($ip);
        if ($bin === false) {
            return null;
        }

        return strlen($bin) === 4 ? str_repeat("\0", 10)."\xff\xff".$bin : $bin;
    }

    public function country(string $ip): ?string
    {
        $bin = self::pack($ip);
        if ($bin === null) {
            return null;
        }

        return Cache::remember('geo:'.sha1($bin), 86400, function () use ($bin) {
            $row = DB::table('geoip_ranges')->where('ip_start', '<=', $bin)->orderByDesc('ip_start')->first(['ip_end', 'country']);

            return $row && strcmp($row->ip_end, $bin) >= 0 && preg_match('/^[A-Z]{2}$/', $row->country) && $row->country !== 'ZZ' ? $row->country : '';
        }) ?: null;
    }

    /**
     * Replaces the table with the rows from a DB-IP country CSV (start,end,country).
     *
     * @param  resource  $handle  readable stream of the uncompressed CSV
     */
    public function import($handle): int
    {
        $rows = [];
        $n = 0;
        DB::transaction(function () use ($handle, &$rows, &$n) {
            DB::table('geoip_ranges')->delete();
            while (($line = fgetcsv($handle, 200, ',', '"', '')) !== false) {
                if (count($line) < 3 || ($a = self::pack(trim($line[0]))) === null || ($b = self::pack(trim($line[1]))) === null) {
                    continue;
                }
                $rows[] = ['ip_start' => $a, 'ip_end' => $b, 'country' => strtoupper(substr(trim($line[2]), 0, 2))];
                if (count($rows) === 2000) {
                    DB::table('geoip_ranges')->insert($rows);
                    $n += count($rows);
                    $rows = [];
                }
            }
            if ($rows) {
                DB::table('geoip_ranges')->insert($rows);
                $n += count($rows);
            }
        });
        if ($n === 0) {
            throw new RuntimeException('The GeoIP file had no rows.');
        }

        return $n;
    }

    public function loaded(): bool
    {
        return DB::table('geoip_ranges')->exists();
    }
}
