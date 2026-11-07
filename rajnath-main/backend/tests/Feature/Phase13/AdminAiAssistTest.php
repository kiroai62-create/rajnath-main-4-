<?php

namespace Tests\Feature\Phase13;

use App\Support\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** "✨ AI" helper under admin text boxes. */
class AdminAiAssistTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
    }

    public function test_staff_get_a_draft_and_customers_cannot_use_it(): void
    {
        $s = app(SettingsRepository::class);
        foreach (['ai.enabled' => true, 'ai.order' => 'groq', 'ai.groq.enabled' => true] as $k => $v) {
            $s->set($k, $v);
        }
        $s->set('ai.groq.key', 'k', secret: true);
        $sent = [];
        Http::fake(['api.groq.com/*' => function ($req) use (&$sent) {
            $sent[] = $req->data();

            return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => "```\nशनि की साढ़े साती <b>सरल</b>\n```"]]]]);
        }]);
        $author = $this->makeStaff(['content_author']);
        $this->asStaff($author)->get(route('admin.blog.create'))->assertOk()->assertSee('name="arj-admin-ai"', false);
        $this->asStaff($author)->postJson(route('admin.ai.assist'), ['action' => 'hindi', 'field' => 'body', 'text' => 'Sade Sati in simple words', 'context' => 'Title: Sade Sati'])
            ->assertOk()->assertJsonPath('text', 'शनि की साढ़े साती सरल');
        $this->assertStringContainsString('Translate the text into natural, simple Hindi', $sent[0]['messages'][1]['content']);
        $this->asStaff($author)->postJson(route('admin.ai.assist'), ['action' => 'hack', 'field' => 'body'])->assertUnprocessable();

        $this->post('/staff/logout');
        $this->postJson(route('admin.ai.assist'), ['action' => 'write', 'field' => 'body'])->assertUnauthorized();

        $s->set('ai.enabled', false);
        $this->asStaff($author)->postJson(route('admin.ai.assist'), ['action' => 'write', 'field' => 'body'])->assertStatus(503)->assertJsonPath('error.code', 'ai_unavailable');
    }
}
