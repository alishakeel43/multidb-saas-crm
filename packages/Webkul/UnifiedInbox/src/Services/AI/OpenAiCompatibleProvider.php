<?php

namespace Webkul\UnifiedInbox\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Webkul\UnifiedInbox\Contracts\AiReplyProvider;

/**
 * Works with any OpenAI-compatible /chat/completions API
 * (OpenAI, OpenRouter, Groq, Together, local Ollama/LM Studio, etc.).
 */
class OpenAiCompatibleProvider implements AiReplyProvider
{
    public function __construct(
        protected ?string $apiKey,
        protected ?string $model,
        protected string $baseUrl,
        protected int $timeout = 20,
        protected bool $requiresApiKey = true
    ) {}

    public function isConfigured(): bool
    {
        if (empty($this->model) || empty($this->baseUrl)) {
            return false;
        }

        return ! $this->requiresApiKey || ! empty($this->apiKey);
    }

    public function complete(array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('AI not configured.');
        }

        $request = Http::timeout($this->timeout)->acceptJson();

        if (! empty($this->apiKey)) {
            $request = $request->withToken($this->apiKey);
        }

        $response = $request
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.6,
                'max_tokens' => 300,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI provider error (HTTP '.$response->status().').');
        }

        return (string) data_get($response->json(), 'choices.0.message.content', '');
    }
}
