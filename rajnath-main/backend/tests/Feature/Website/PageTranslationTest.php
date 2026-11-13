<?php

namespace Tests\Feature\Website;

use App\Domain\Translation\PageTranslator;
use App\Domain\Translation\SiteTranslations;
use App\Support\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Every page follows the language button: lang/*.json first, then the AI-filled translation memory. */
class PageTranslationTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    private function enableAll(): void
    {
        $this->syncRbac();
        app(SettingsRepository::class)->set('site.locales', array_keys(config('locales.available')));
    }

    private function aiKey(): void
    {
        $s = app(SettingsRepository::class);
        $s->set('ai.groq.key', 'gsk_test', secret: true);
        $s->set('ai.groq.enabled', true);
    }

    public function test_translator_replaces_text_but_keeps_markup_scripts_and_private_parts(): void
    {
        DB::table('site_translations')->insert([
            ['locale' => 'ta', 'hash' => sha1('Hello world'), 'source' => 'Hello world', 'text' => 'வணக்கம் உலகம்', 'status' => 'done', 'origin' => 'ai', 'created_at' => now(), 'updated_at' => now()],
            ['locale' => 'ta', 'hash' => sha1('Your name'), 'source' => 'Your name', 'text' => 'உங்கள் பெயர்', 'status' => 'done', 'origin' => 'ai', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $html = '<p class="x" :class="n > 1 ? \'a\' : \'b\'">  Hello   world </p><script>if (a < b) { t = "Hello world"; }</script>'
            .'<input placeholder="Your name"><span translate="no">Hello world</span><code>Hello world</code><b>Unknown text here</b>';
        $out = app(PageTranslator::class)->translate($html, 'ta', true, ['Unknown text']);
        $this->assertStringContainsString('<p class="x" :class="n > 1 ? \'a\' : \'b\'">  வணக்கம் உலகம் </p>', $out);
        $this->assertStringContainsString('<script>if (a < b) { t = "Hello world"; }</script>', $out);
        $this->assertStringContainsString('placeholder="உங்கள் பெயர்"', $out);
        $this->assertStringContainsString('<span translate="no">Hello world</span>', $out);
        $this->assertStringContainsString('<code>Hello world</code>', $out);
        // text containing what the visitor typed is never sent to AI
        $this->assertFalse(DB::table('site_translations')->where('source', 'Unknown text here')->exists());
    }

    public function test_all_nine_languages_are_offered_and_switch_the_whole_page(): void
    {
        $this->enableAll();
        $html = $this->get('/faq')->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="en">', $html);
        foreach (['hi', 'bn', 'mr', 'te', 'ta', 'gu', 'ur', 'kn'] as $l) {
            $this->assertStringContainsString('hreflang="'.$l.'"', $html, $l);
        }
        $this->get('/faq?lang=ur')->assertOk()->assertSee('<html lang="ur" dir="rtl">', false);
        // the reviewed lang file is used first
        $this->get('/faq?lang=hi')->assertOk()->assertSee('<html lang="hi">', false)->assertSee('सेवाएँ');
        // the choice is remembered
        $this->get('/contact')->assertSee('<html lang="hi">', false);
        // unseen text waits for AI (stays English meanwhile)
        $this->get('/contact?lang=ta')->assertOk();
        $this->assertTrue(DB::table('site_translations')->where('locale', 'ta')->where('status', 'pending')->exists());
        // the control centre stays English
        $this->asStaff($this->makeStaff(['owner']))->get('/admin?lang=ta')->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_ai_fills_waiting_text_and_staff_corrections_win(): void
    {
        $this->enableAll();
        $this->aiKey();
        // opening a page queues its untranslated text
        $this->get('/contact?lang=gu')->assertOk();
        $row = DB::table('site_translations')->where('locale', 'gu')->where('status', 'pending')->orderBy('id')->first();
        $this->assertNotNull($row);
        $id = $row->id;
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => '```json'."\n".json_encode([(string) $id => 'ગુજરાતી લખાણ'])."\n```"]]]])]);
        $this->assertSame(1, app(SiteTranslations::class)->translateBatch('gu', 1));
        $this->get('/contact?lang=gu')->assertOk()->assertSee('ગુજરાતી લખાણ');

        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.languages.index', ['locale' => 'gu', 'status' => 'done']))->assertOk()->assertSee(route('admin.languages.line', $id), false)->assertSee('ગુજરાતી લખાણ');
        $this->asStaff($owner)->put(route('admin.languages.line', $id), ['text' => 'અમારો સંપર્ક કરો'])->assertRedirect();
        $this->assertSame('manual', DB::table('site_translations')->where('id', $id)->value('origin'));
        $this->get('/contact?lang=gu')->assertSee('અમારો સંપર્ક કરો');
    }

    public function test_owner_chooses_the_languages(): void
    {
        $this->syncRbac();
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->put(route('admin.languages.update'), ['locales' => ['hi', 'ta'], 'auto' => '1'])->assertRedirect();
        $html = $this->get('/faq')->getContent();
        $this->assertStringContainsString('hreflang="ta"', $html);
        $this->assertStringNotContainsString('hreflang="bn"', $html);
        $this->asStaff($owner)->put(route('admin.languages.update'), ['locales' => []])->assertRedirect();
        $this->get('/faq')->assertDontSee('aria-controls="lang-menu"', false);
    }
}
