<?php

namespace App\Jobs;

use App\Domain\Translation\PageTranslator;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Throwable;

/**
 * "Translate the whole website now": opens every page in the sitemap once in each language, so
 * all of its text is queued for AI translation before the first visitor arrives.
 */
class ScanSitePages implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    /** @param  list<string>  $locales */
    public function __construct(public array $locales = [], public int $maxPages = 400) {}

    public function handle(): void
    {
        $locales = array_values(array_diff($this->locales ?: SetLocale::enabled(), ['en']));
        if (! $locales) {
            return;
        }
        $paths = array_slice(self::paths(), 0, $this->maxPages);
        $kernel = app(Kernel::class);
        foreach ($locales as $locale) {
            foreach ($paths as $path) {
                try {
                    $request = Request::create($path, 'GET', ['lang' => $locale]);
                    $response = $kernel->handle($request);
                    $kernel->terminate($request, $response);
                } catch (Throwable $e) {
                    report($e);
                }
            }
            PageTranslator::kick($locale);
        }
    }

    /** @return list<string> Website paths from the sitemap, home page first. */
    public static function paths(): array
    {
        $kernel = app(Kernel::class);
        $request = Request::create('/sitemap.xml', 'GET');
        $xml = (string) $kernel->handle($request)->getContent();
        preg_match_all('#<loc>\s*([^<]+?)\s*</loc>#', $xml, $m);
        $paths = ['/'];
        foreach ($m[1] as $url) {
            $path = parse_url(html_entity_decode($url), PHP_URL_PATH) ?: '/';
            if (! str_contains((string) parse_url(html_entity_decode($url), PHP_URL_QUERY), 'lang=')) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique([...$paths, '/puja', '/contact', '/tools', '/panchang']));
    }
}
