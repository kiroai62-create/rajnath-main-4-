<?php

namespace Tests\Feature\Phase13;

use App\Domain\Assistant\AiProviders;
use App\Support\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Groq "empty reply" on Test: reasoning models used the whole 20-token limit thinking. */
class AiProviderWiringTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    /** @var list<array<string, mixed>> */
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->syncRbac();
    }

    private function groq(string $model = ''): void
    {
        $s = app(SettingsRepository::class);
        $s->set('ai.enabled', true);
        $s->set('ai.order', 'groq');
        $s->set('ai.groq.enabled', true);
        $s->set('ai.groq.key', 'gsk_test', secret: true);
        $s->set('ai.groq.model', $model);
    }

    public function test_groq_reasoning_model_gets_low_effort_and_room_to_answer(): void
    {
        $this->groq();
        Http::fake(['api.groq.com/*' => function ($req) {
            $this->sent[] = $req->data();

            return Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'OK'], 'finish_reason' => 'stop']]]);
        }]);
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->post(route('admin.assistant.test'), ['provider' => 'groq'])->assertSessionHasNoErrors()->assertSessionHas('status');
        $body = $this->sent[0];
        $this->assertSame('openai/gpt-oss-120b', $body['model']);
        $this->assertGreaterThanOrEqual(256, $body['max_tokens']);
        $this->assertSame('low', $body['reasoning_effort']);
        $this->assertFalse($body['include_reasoning']);
    }

    public function test_cut_off_answer_is_retried_with_a_bigger_limit(): void
    {
        $this->groq();
        Http::fake(['api.groq.com/*' => function ($req) {
            $this->sent[] = $req->data();

            return count($this->sent) === 1
                ? Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => ''], 'finish_reason' => 'length']]])
                : Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'OK'], 'finish_reason' => 'stop']]]);
        }]);
        [$text] = app(AiProviders::class)->call('groq', [['role' => 'user', 'content' => 'hi']], 100);
        $this->assertSame('OK', $text);
        $this->assertCount(2, $this->sent);
        $this->assertGreaterThan(100, $this->sent[1]['max_tokens']);
    }

    public function test_non_reasoning_model_gets_plain_body_and_openai_uses_completion_tokens(): void
    {
        $this->groq('llama-3.1-8b-instant');
        $ai = app(AiProviders::class);
        $body = $ai->body('groq', [], 300);
        $this->assertArrayNotHasKey('reasoning_effort', $body);
        $this->assertSame(300, $body['max_tokens']);
        app(SettingsRepository::class)->set('ai.openai.model', 'gpt-6-luna');
        $o = $ai->body('openai', [], 300);
        $this->assertSame(300, $o['max_completion_tokens']);
        $this->assertArrayNotHasKey('max_tokens', $o);
        $this->assertArrayNotHasKey('temperature', $o);
    }

    public function test_app_chat_answers_through_groq(): void
    {
        $this->groq();
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['role' => 'assistant', 'content' => 'Namaste ji'], 'finish_reason' => 'stop']]])]);
        $this->postJson('/api/v1/assistant/chat', ['message' => 'Aaj ka panchang?'])->assertOk()->assertJsonPath('reply', 'Namaste ji')->assertJsonPath('mode', 'ai');
    }
}
