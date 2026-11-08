<?php

namespace Tests\Feature\Phase13;

use App\Models\User;
use App\Support\Settings\SettingsRepository;
use Database\Seeders\ShowcaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Admin › Push messages and gallery "Captions with AI". */
class AdminAiFormsTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
        Mail::fake();
        $s = app(SettingsRepository::class);
        foreach (['ai.enabled' => true, 'ai.order' => 'groq', 'ai.groq.enabled' => true] as $k => $v) {
            $s->set($k, $v);
        }
        $s->set('ai.groq.key', 'k', secret: true);
    }

    private function aiReplies(array $json): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => json_encode($json, JSON_UNESCAPED_UNICODE)]]]])]);
    }

    private function customer(string $email, string $status = User::STATUS_ACTIVE): User
    {
        $u = new User(['name' => 'C '.$email, 'email' => $email]);
        $u->kind = User::KIND_CUSTOMER;
        $u->status = $status;
        $u->save();

        return $u;
    }

    public function test_publisher_drafts_with_ai_and_sends_to_every_active_customer_once(): void
    {
        $a = $this->customer('a@x.test');
        $b = $this->customer('b@x.test');
        $this->customer('s@x.test', User::STATUS_SUSPENDED);
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.broadcast.index'))->assertOk()->assertSee('Write with AI')->assertSee('2</strong> customers', false);

        $this->aiReplies(['title' => 'दीवाली पूजा बुकिंग शुरू 🪔', 'body' => 'लक्ष्मी पूजा अपने नाम से करवाएँ।']);
        $this->asStaff($owner)->post(route('admin.broadcast.ai'), ['topic' => 'Diwali puja bookings open', 'language' => 'hi'])
            ->assertSessionHasInput('title', 'दीवाली पूजा बुकिंग शुरू 🪔')->assertSessionHasNoErrors();
        $this->assertSame(0, DB::table('user_notifications')->count(), 'AI draft sends nothing');

        $this->asStaff($owner)->post(route('admin.broadcast.send'), ['title' => 'Diwali', 'body' => 'Book now', 'kind' => 'general', 'link' => 'javascript:x', 'confirm' => '1'])->assertSessionHasErrors('link');
        $this->asStaff($owner)->post(route('admin.broadcast.send'), ['title' => 'Diwali', 'body' => 'Book now', 'kind' => 'general', 'link' => 'app:puja'])->assertSessionHasErrors('confirm');
        $this->asStaff($owner)->post(route('admin.broadcast.send'), ['title' => 'Diwali', 'body' => 'Book now', 'kind' => 'general', 'link' => 'app:puja', 'confirm' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.broadcast.index'));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], DB::table('user_notifications')->pluck('user_id')->all());
        $this->assertSame('app:puja', DB::table('user_notifications')->value('link'));
        $this->asStaff($owner)->get(route('admin.broadcast.index'))->assertSee('Announcement');

        $this->asStaff($this->makeStaff(['support']))->get(route('admin.broadcast.index'))->assertForbidden();
        $this->asStaff($owner, freshStepUp: false)->post(route('admin.broadcast.send'), ['title' => 'x', 'body' => 'y', 'kind' => 'offer', 'confirm' => '1'])->assertRedirect();
        $this->assertSame(2, DB::table('user_notifications')->count(), 'sending needs a fresh step-up');
    }

    public function test_gallery_ai_suggests_captions_for_review_then_saves_them(): void
    {
        $this->seed(ShowcaseSeeder::class);
        $album = DB::table('gallery_albums')->first();
        DB::table('gallery_items')->where('album_id', $album->id)->update(['caption' => null]);
        $ids = DB::table('gallery_items')->where('album_id', $album->id)->orderBy('id')->pluck('id')->all();
        $this->assertNotEmpty($ids);
        $this->aiReplies(['captions' => [['id' => $ids[0], 'en' => 'Award ceremony in Kuala Lumpur', 'hi' => 'कुआलालंपुर में सम्मान समारोह'], ['id' => 999999, 'en' => 'not in album']]]);
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->post(route('admin.gallery.ai-captions', $album->ref))->assertSessionHas('ai_captions', [$ids[0] => ['en' => 'Award ceremony in Kuala Lumpur', 'hi' => 'कुआलालंपुर में सम्मान समारोह']]);
        $this->assertNull(DB::table('gallery_items')->where('id', $ids[0])->value('caption'), 'nothing saved yet');
        $this->asStaff($owner)->put(route('admin.gallery.captions', $album->ref), ['captions' => [$ids[0] => ['en' => 'Award ceremony, Kuala Lumpur', 'hi' => 'सम्मान समारोह']]])->assertSessionHasNoErrors();
        $this->assertSame('Award ceremony, Kuala Lumpur', DB::table('gallery_items')->where('id', $ids[0])->value('caption'));
        $this->asStaff($owner)->get(route('admin.gallery.edit', $album->ref))->assertOk()->assertSee('Suggest captions');
    }
}
