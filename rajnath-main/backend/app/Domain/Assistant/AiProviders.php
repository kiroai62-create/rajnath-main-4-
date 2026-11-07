<?php

namespace App\Domain\Assistant;

use App\Support\Settings\SettingsRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Groq, Cloudflare Workers AI, OpenAI and Google Gemini through their OpenAI-compatible
 * Chat Completions endpoints. Providers are tried in the owner's chosen order; the first one
 * that answers wins. Keys are stored encrypted in settings. Model names change often, so they
 * are editable in Admin › AI assistant (defaults below were current in October 2026).
 */
class AiProviders
{
    public const PROVIDERS = [
        'groq' => ['label' => 'Groq', 'base' => 'https://api.groq.com/openai/v1', 'model' => 'openai/gpt-oss-120b', 'keys' => 'console.groq.com › API Keys'],
        'cloudflare' => ['label' => 'Cloudflare Workers AI', 'base' => 'https://api.cloudflare.com/client/v4/accounts/{account}/ai/v1', 'model' => '@cf/openai/gpt-oss-120b', 'keys' => 'dash.cloudflare.com › AI › Workers AI › Use REST API (token with Workers AI permission) + Account ID'],
        'openai' => ['label' => 'OpenAI', 'base' => 'https://api.openai.com/v1', 'model' => '', 'keys' => 'platform.openai.com › API keys. Enter a current model id from platform.openai.com/docs/models'],
        'gemini' => ['label' => 'Google Gemini', 'base' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => 'gemini-3.8-flash', 'keys' => 'aistudio.google.com › Get API key'],
    ];

    /** Total time one assistant chat may spend on AI calls. Kept well below the app's 90 s wait. */
    public const CHAT_BUDGET_SECONDS = 55;

    public function __construct(private SettingsRepository $settings) {}

    public function model(string $p): string
    {
        return (string) ($this->settings->get("ai.$p.model") ?: self::PROVIDERS[$p]['model']);
    }

    public function configured(string $p): bool
    {
        return (bool) $this->settings->get("ai.$p.key") && $this->model($p) !== '' && ($p !== 'cloudflare' || $this->settings->get('ai.cloudflare.account_id'));
    }

    /** Enabled providers in fallback order. */
    public function order(): array
    {
        $order = array_filter(explode(',', (string) $this->settings->get('ai.order', 'groq,gemini,cloudflare,openai')));

        return array_values(array_filter(array_unique($order), fn ($p) => isset(self::PROVIDERS[$p]) && $this->settings->get("ai.$p.enabled") && $this->configured($p)));
    }

    public function available(): bool
    {
        return (bool) $this->settings->get('ai.enabled') && $this->order() !== [];
    }

    private function url(string $p): string
    {
        $base = self::PROVIDERS[$p]['base'];
        if ($p === 'cloudflare') {
            $acct = (string) $this->settings->get('ai.cloudflare.account_id');
            if (! preg_match('/^[a-f0-9]{32}$/', $acct)) {
                throw new RuntimeException('Cloudflare account ID looks wrong.');
            }
            $base = str_replace('{account}', $acct, $base);
        }

        return $base.'/chat/completions';
    }

    /**
     * Whether the model "thinks" before answering (GPT-OSS, Qwen 3, o-series, GPT-5+, Gemini 2.5+/3).
     * Thinking tokens count against the token limit, so a small limit can leave the answer empty.
     */
    public static function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('#(gpt-oss|qwen3|(^|/)o\d|gpt-[5-9]|gemini-(2\.5|[3-9])|muse-spark|minimax|deepseek-r)#i', $model);
    }

    /**
     * Request body for one Chat Completions call, with the token and reasoning parameters
     * each provider expects. Reasoning models get a low effort so the answer is not cut off.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    public function body(string $p, array $messages, int $maxTokens): array
    {
        $model = $this->model($p);
        $reasoning = self::isReasoningModel($model);
        $body = ['model' => $model, 'messages' => $messages];
        if ($p === 'openai') {
            // Current OpenAI models only accept max_completion_tokens, and reasoning models reject a custom temperature.
            $body['max_completion_tokens'] = $maxTokens;
            if ($reasoning) {
                $body['reasoning_effort'] = 'low';
            } else {
                $body['temperature'] = 0.3;
            }

            return $body;
        }
        $body['temperature'] = 0.3;
        $body['max_tokens'] = $maxTokens;
        if ($reasoning && in_array($p, ['groq', 'gemini'], true)) {
            $body['reasoning_effort'] = 'low';
        }
        if ($reasoning && $p === 'groq' && stripos($model, 'gpt-oss') !== false) {
            $body['include_reasoning'] = false;
        }

        return $body;
    }

    /** Readable error from a failed provider response. */
    private function errorFrom(string $p, Response $res): RuntimeException
    {
        $err = $res->json('error.message') ?? $res->json('errors.0.message') ?? $res->json('0.error.message') ?? ('HTTP '.$res->status());

        return new RuntimeException(self::PROVIDERS[$p]['label'].': '.mb_substr((string) $err, 0, 200));
    }

    /**
     * One provider call. Returns [text, tokens_in, tokens_out, latency_ms].
     * If a reasoning model spends the whole token limit thinking (empty answer, finish_reason
     * "length"), the call is retried once with a larger limit.
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function call(string $p, array $messages, int $maxTokens = 700, ?int $timeout = null): array
    {
        $t0 = hrtime(true);
        $limit = $maxTokens;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $res = Http::withToken((string) $this->settings->get("ai.$p.key"))->acceptJson()->timeout($timeout ?? ($limit > 1000 ? 120 : 30))->connectTimeout(5)
                ->post($this->url($p), $this->body($p, $messages, $limit));
            if (! $res->successful()) {
                throw $this->errorFrom($p, $res);
            }
            $text = trim((string) $res->json('choices.0.message.content'));
            if ($text !== '') {
                return [$text, $res->json('usage.prompt_tokens'), $res->json('usage.completion_tokens'), (int) ((hrtime(true) - $t0) / 1e6)];
            }
            if ($res->json('choices.0.finish_reason') !== 'length' || $limit >= 4000) {
                break;
            }
            $limit = max(1024, $limit * 4);
        }
        $why = $res->json('choices.0.finish_reason') === 'length' ? ' (the model used all its tokens thinking; try a non-reasoning model)' : '';

        throw new RuntimeException(self::PROVIDERS[$p]['label'].': empty reply'.$why);
    }

    /**
     * Tries providers in order. Returns [provider, model, text, tokens_in, tokens_out, latency_ms].
     *
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(array $messages, int $maxTokens = 700): array
    {
        $errors = [];
        foreach ($this->order() as $p) {
            try {
                [$text, $in, $out, $ms] = $this->call($p, $messages, $maxTokens);

                return [$p, $this->model($p), $text, $in, $out, $ms];
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
                report($e);
            }
        }
        throw new RuntimeException('No AI provider answered. '.implode(' | ', $errors));
    }

    /**
     * Chat with function calling (OpenAI "tools" format, supported by all four providers).
     * Runs the tool loop on one provider; on an error it moves to the next provider and, as a
     * last resort, answers without tools. Returns [provider, model, text, tokens_in, tokens_out, latency_ms].
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @param  callable(string, array): array  $runTool  name, arguments → JSON-serialisable result
     */
    public function completeWithTools(array $messages, array $tools, callable $runTool, int $maxSteps = 6, int $budgetSeconds = self::CHAT_BUDGET_SECONDS): array
    {
        $errors = [];
        $deadline = microtime(true) + $budgetSeconds;
        foreach ($this->order() as $p) {
            $t0 = hrtime(true);
            $convo = $messages;
            $in = $out = 0;
            try {
                for ($step = 0; $step < $maxSteps; $step++) {
                    $left = (int) floor($deadline - microtime(true));
                    if ($left < 4) {
                        throw new RuntimeException(self::PROVIDERS[$p]['label'].': out of time');
                    }
                    $body = $this->body($p, $convo, 1200);
                    if ($tools && $step < $maxSteps - 1) {
                        $body += ['tools' => $tools, 'tool_choice' => 'auto'];
                    }
                    $res = Http::withToken((string) $this->settings->get("ai.$p.key"))->acceptJson()->timeout(min(40, $left))->connectTimeout(5)->post($this->url($p), $body);
                    if (! $res->successful()) {
                        throw $this->errorFrom($p, $res);
                    }
                    $in += (int) $res->json('usage.prompt_tokens');
                    $out += (int) $res->json('usage.completion_tokens');
                    $msg = (array) $res->json('choices.0.message');
                    $calls = $msg['tool_calls'] ?? [];
                    if (! $calls) {
                        $text = trim((string) ($msg['content'] ?? ''));
                        if ($text === '') {
                            throw new RuntimeException(self::PROVIDERS[$p]['label'].': empty reply');
                        }

                        return [$p, $this->model($p), $text, $in, $out, (int) ((hrtime(true) - $t0) / 1e6)];
                    }
                    $convo[] = ['role' => 'assistant', 'content' => $msg['content'] ?? null, 'tool_calls' => $calls];
                    foreach (array_slice($calls, 0, 4) as $call) {
                        $args = json_decode((string) ($call['function']['arguments'] ?? '{}'), true);
                        try {
                            $result = $runTool((string) ($call['function']['name'] ?? ''), is_array($args) ? $args : []);
                        } catch (Throwable $e) {
                            $result = ['error' => mb_substr($e->getMessage(), 0, 300)];
                        }
                        $convo[] = ['role' => 'tool', 'tool_call_id' => (string) ($call['id'] ?? ''), 'name' => (string) ($call['function']['name'] ?? ''),
                            'content' => mb_substr((string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 12000)];
                    }
                }
                throw new RuntimeException(self::PROVIDERS[$p]['label'].': too many tool steps');
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
                report($e);
            }
        }
        $left = (int) floor($deadline - microtime(true));
        if ($errors && $this->order() !== [] && $left >= 8) {
            $plain = array_values(array_filter($messages, fn ($m) => in_array($m['role'], ['system', 'user', 'assistant'], true) && is_string($m['content'] ?? null)));
            foreach ($this->order() as $p) {
                try {
                    [$text, $in, $out, $ms] = $this->call($p, $plain, 900, min(25, max(5, (int) floor($deadline - microtime(true)))));

                    return [$p, $this->model($p), $text, $in, $out, $ms];
                } catch (Throwable $e) {
                    $errors[] = $e->getMessage();
                    report($e);
                }
                if ($deadline - microtime(true) < 6) {
                    break;
                }
            }
        }
        throw new RuntimeException('No AI provider answered. '.implode(' | ', $errors));
    }

    /**
     * Searches the internet through whichever provider offers it: Gemini (Google Search
     * grounding), OpenAI (web search tool) or Groq (compound model with web search).
     * Returns ['answer' => text, 'sources' => [[title, url]...]] or null when none is set up.
     */
    public function webSearch(string $query): ?array
    {
        if (! $this->settings->get('ai.web_search', true)) {
            return null;
        }
        $query = mb_substr(trim($query), 0, 300);
        foreach (['gemini', 'openai', 'groq'] as $p) {
            if (! $this->settings->get("ai.$p.enabled") || ! $this->settings->get("ai.$p.key")) {
                continue;
            }
            try {
                $key = (string) $this->settings->get("ai.$p.key");
                if ($p === 'gemini') {
                    $res = Http::withHeaders(['x-goog-api-key' => $key])->acceptJson()->timeout(20)
                        ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($this->model('gemini')).':generateContent', [
                            'contents' => [['role' => 'user', 'parts' => [['text' => $query]]]], 'tools' => [['google_search' => (object) []]],
                        ]);
                    $text = collect($res->json('candidates.0.content.parts') ?? [])->pluck('text')->implode('');
                    $sources = collect($res->json('candidates.0.groundingMetadata.groundingChunks') ?? [])->map(fn ($c) => [$c['web']['title'] ?? '', $c['web']['uri'] ?? ''])->take(5)->all();
                } elseif ($p === 'openai') {
                    $res = Http::withToken($key)->acceptJson()->timeout(20)->post('https://api.openai.com/v1/responses', [
                        'model' => $this->model('openai'), 'input' => $query, 'tools' => [['type' => 'web_search']],
                    ]);
                    $text = (string) ($res->json('output_text') ?? collect($res->json('output') ?? [])->where('type', 'message')->flatMap(fn ($m) => $m['content'] ?? [])->pluck('text')->implode(''));
                    $sources = collect($res->json('output') ?? [])->where('type', 'message')->flatMap(fn ($m) => $m['content'] ?? [])
                        ->flatMap(fn ($c) => $c['annotations'] ?? [])->map(fn ($a) => [$a['title'] ?? '', $a['url'] ?? ''])->filter(fn ($s) => $s[1])->unique(1)->take(5)->values()->all();
                } else {
                    $res = Http::withToken($key)->acceptJson()->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => 'groq/compound', 'messages' => [['role' => 'user', 'content' => $query]],
                    ]);
                    $text = (string) $res->json('choices.0.message.content');
                    $sources = [];
                }
                if ($res->successful() && trim($text) !== '') {
                    return ['answer' => mb_substr(trim($text), 0, 4000), 'sources' => array_values(array_filter($sources, fn ($s) => str_starts_with((string) $s[1], 'https://')))];
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return null;
    }
}
