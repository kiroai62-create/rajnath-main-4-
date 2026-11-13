<?php

namespace App\Console\Commands;

use App\Domain\Analytics\GeoIp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Loads the free DB-IP "IP to Country Lite" list (CC BY 4.0) so visitor countries are found
 * on this server. Run by deploy/deploy.sh and monthly by the scheduler.
 *
 *   php artisan arj:geoip-import               # download this month's file
 *   php artisan arj:geoip-import --file=x.csv.gz
 */
class GeoIpImport extends Command
{
    protected $signature = 'arj:geoip-import {--file= : Local .csv or .csv.gz file instead of downloading}';

    protected $description = 'Load the IP-to-country table used by Admin › Analytics.';

    public function handle(GeoIp $geo): int
    {
        $path = (string) $this->option('file');
        $tmp = null;
        try {
            if ($path === '') {
                $tmp = storage_path('app/dbip-country.csv.gz');
                foreach ([now(), now()->subMonthNoOverflow()] as $month) {
                    $url = sprintf(GeoIp::SOURCE, $month->format('Y-m'));
                    $res = Http::timeout(180)->sink($tmp)->get($url);
                    if ($res->successful()) {
                        break;
                    }
                }
                if (! isset($res) || ! $res->successful()) {
                    $this->warn('Could not download the GeoIP list; countries will show as Unknown until the next try.');

                    return self::SUCCESS;
                }
                $path = $tmp;
            }
            $handle = str_ends_with($path, '.gz') ? gzopen($path, 'r') : fopen($path, 'r');
            if (! $handle) {
                $this->error('Cannot read '.$path);

                return self::FAILURE;
            }
            $n = $geo->import($handle);
            fclose($handle);
            $this->info("GeoIP ranges loaded: $n (IP geolocation by DB-IP, CC BY 4.0).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->warn('GeoIP import failed: '.$e->getMessage());

            return self::SUCCESS;   // never stop a deploy because of this
        } finally {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }
}
