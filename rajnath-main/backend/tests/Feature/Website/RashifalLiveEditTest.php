<?php

namespace Tests\Feature\Website;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Admin › Rashifal shows today's live text per sign; an edit goes live at once and AI keeps it. */
class RashifalLiveEditTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    public function test_owner_sees_and_edits_live_rashifal(): void
    {
        $this->syncRbac();
        $today = now('Asia/Kolkata')->toDateString();
        $id = DB::table('horoscopes')->insertGetId([
            'kind' => 'rashi', 'topic' => 'general', 'period' => 'daily', 'sign' => 0, 'starts_on' => $today, 'ends_on' => $today, 'locale' => 'hi',
            'body' => 'मेष राशि के लिए आज का दिन अच्छा रहेगा। कार्यक्षेत्र में सफलता मिलेगी और परिवार का साथ मिलेगा।', 'status' => 'published', 'source' => 'ai',
            'author_id' => null, 'published_at' => now(), 'version' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.rashifal.live', ['kind' => 'rashi', 'period' => 'daily', 'locale' => 'hi']))->assertOk()
            ->assertSee('मेष राशि के लिए आज का दिन अच्छा रहेगा')->assertSee('AI');

        $new = 'मेष राशि: आज धैर्य रखें। नए काम की शुरुआत दोपहर के बाद करें, हनुमान चालीसा का पाठ लाभ देगा।';
        $this->asStaff($owner)->put(route('admin.rashifal.update', $id), ['body' => $new, 'version' => 1])->assertRedirect();
        $row = DB::table('horoscopes')->find($id);
        $this->assertSame($new, $row->body);
        $this->assertSame('staff', $row->source);
        $this->assertSame(2, (int) $row->version);
        $this->assertTrue(DB::table('audit_logs')->where('action', 'content.horoscope_edited')->exists());
        $this->get('/rashifal?period=daily&lang=hi')->assertOk()->assertSee('आज धैर्य रखें');

        $author = $this->makeStaff(['content_author']);   // can read, cannot change live text
        $this->asStaff($author)->get(route('admin.rashifal.live'))->assertOk();
        $this->asStaff($author)->put(route('admin.rashifal.update', $id), ['body' => str_repeat('x', 50), 'version' => 2])->assertForbidden();
        // an old copy of the page cannot overwrite a newer edit
        $this->asStaff($owner)->put(route('admin.rashifal.update', $id), ['body' => str_repeat('पुराना पाठ ', 6), 'version' => 1])->assertSessionHasErrors('body');
    }
}
