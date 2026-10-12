<?php

namespace Webkul\UnifiedInbox\Services\AI;

use Webkul\UnifiedInbox\Contracts\AiReplyProvider;

/**
 * Resolves the AI provider for Unified Inbox reply suggestions.
 *
 * Providers (UNIFIED_INBOX_AI_PROVIDER):
 *  - grok   : xAI, base https://api.x.ai/v1, default model grok-3-mini
 *  - custom : any local/self-hosted OpenAI-compatible LLM (Ollama, LM Studio, vLLM…).
 *             UNIFIED_INBOX_AI_BASE_URL + UNIFIED_INBOX_AI_MODEL are required; API key optional.
 *  - openai : base https://api.openai.com/v1, default model gpt-4o-mini
 *  (aliases also accepted: xai → grok, local/ollama/lmstudio → custom, openrouter, groq)
 *
 * Precedence:
 *  1) ENV config (config/unified-inbox.php "ai") wins when an API key is set,
 *     or when provider=custom with a base URL (local LLMs often need no key).
 *  2) Otherwise falls back to Krayin's Magic AI settings (OpenRouter) if enabled.
 *  3) Otherwise the provider reports "not configured" and the UI shows that.
 */
class AiProviderFactory
{
    public const DEFAULTS = [
        'grok' => ['base_url' => 'https://api.x.ai/v1', 'model' => 'grok-3-mini'],
        'openai' => ['base_url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini'],
        'openrouter' => ['base_url' => 'https://openrouter.ai/api/v1', 'model' => 'openai/gpt-4o-mini'],
        'groq' => ['base_url' => 'https://api.groq.com/openai/v1', 'model' => 'llama-3.1-8b-instant'],
        'custom' => ['base_url' => null, 'model' => null],
    ];

    public static function normalizeProvider(?string $provider): string
    {
        $p = strtolower(trim((string) $provider));

        return match ($p) {
            'xai', 'grok' => 'grok',
            'custom', 'local', 'ollama', 'lmstudio', 'lm-studio' => 'custom',
            'openrouter' => 'openrouter',
            'groq' => 'groq',
            default => 'openai',
        };
    }

    public static function make(): AiReplyProvider
    {
        $cfg = config('unified-inbox.ai', []);
        $provider = self::normalizeProvider($cfg['provider'] ?? 'grok');

        $apiKey = $cfg['api_key'] ?: null;
        $model = $cfg['model'] ?: null;
        $baseUrl = $cfg['base_url'] ?: null;
        $timeout = (int) ($cfg['timeout'] ?? 20);

        $envConfigured = ! empty($apiKey) || ($provider === 'custom' && ! empty($baseUrl));

        if (! $envConfigured) {
            $magicEnabled = (bool) self::coreConfig('general.magic_ai.settings.enable');
            $magicKey = self::coreConfig('general.magic_ai.settings.api_key');

            if ($magicEnabled && $magicKey) {
                $provider = 'openrouter';
                $apiKey = $magicKey;
                $model = self::coreConfig('general.magic_ai.settings.other_model')
                    ?: self::coreConfig('general.magic_ai.settings.model');
                $baseUrl = null;
            }
        }

        $defaults = self::DEFAULTS[$provider] ?? self::DEFAULTS['openai'];
        $baseUrl = $baseUrl ?: $defaults['base_url'];
        $model = $model ?: $defaults['model'];

        return new OpenAiCompatibleProvider(
            $apiKey,
            $model,
            (string) $baseUrl,
            $timeout,
            requiresApiKey: $provider !== 'custom'
        );
    }

    protected static function coreConfig(string $key)
    {
        try {
            return core()->getConfigData($key);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
