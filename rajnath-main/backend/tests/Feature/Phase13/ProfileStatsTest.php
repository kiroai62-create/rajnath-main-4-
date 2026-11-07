<?php

namespace Tests\Feature\Phase13;

use App\Domain\Showcase\Profile;
use App\Support\Settings\SettingsRepository;
use Database\Seeders\ProfileStatsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Home and About stats: 1,35,000+ clients and 100+ awards (owner, 7 Nov 2026). */
class ProfileStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_and_saved_values_show_the_new_numbers_once(): void
    {
        $this->assertSame('1,35,000+', app(Profile::class)->stats()['clients']);

        $s = app(SettingsRepository::class);
        $s->set('profile.stat_clients', '1,00,000+');
        $s->set('profile.biography', 'He has guided more than 1,00,000 clients.');
        $this->seed(ProfileStatsSeeder::class);
        $this->assertSame('1,35,000+', $s->get('profile.stat_clients'));
        $this->assertSame('100+', $s->get('profile.stat_awards'));
        $this->assertSame('He has guided more than 1,35,000 clients.', $s->get('profile.biography'));

        $s->set('profile.stat_awards', '120+');
        $this->seed(ProfileStatsSeeder::class);
        $this->assertSame('120+', $s->get('profile.stat_awards'), 'later admin edits are kept');
        $this->assertSame('120+', app(Profile::class)->stats()['awards']);
        $this->get('/')->assertOk()->assertSee('1,35,000+');
    }
}
