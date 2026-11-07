<?php

namespace Tests\Feature\Website;

use App\Support\Navigation;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\MarketPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The main menu is built from the live catalogue and lists only services that are on sale. */
class MegaMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_panels_follow_the_catalogue(): void
    {
        $this->seed(CatalogueSeeder::class);
        $this->seed(MarketPriceSeeder::class);
        $html = $this->get('/')->assertOk()->getContent();
        foreach (['Consultations', 'Reports', 'Puja &amp; remedies', 'Horoscope', 'Free tools', 'About'] as $label) {
            $this->assertStringContainsString($label, $html, $label);
        }
        $this->assertStringContainsString('aria-controls="mega-reports"', $html);
        $this->assertStringContainsString(route('services.show', 'career-report'), $html);
        $this->assertStringContainsString(route('rashi', 'mesha'), $html);
        $this->assertStringContainsString(route('tools.show', 'sade-sati'), $html);
        // drafts are not in the menu; switching a service off removes it
        $this->assertStringNotContainsString(route('services.show', 'mahamrityunjay-puja'), $html);
        DB::table('offerings')->where('slug', 'career-report')->update(['status' => 'draft']);
        Navigation::forget();
        $this->assertStringNotContainsString(route('services.show', 'career-report'), $this->get('/faq')->getContent());
        // every link in the menu answers
        $links = collect(Navigation::menu())->flatMap(fn ($p) => collect($p['columns'])->flatMap(fn ($c) => array_column($c[1], 1))->push($p['href']))->unique();
        foreach ($links as $url) {
            $this->get($url)->assertOk();
        }
    }
}
