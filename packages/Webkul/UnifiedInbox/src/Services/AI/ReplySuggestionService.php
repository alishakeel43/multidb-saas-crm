<?php

namespace Webkul\UnifiedInbox\Services\AI;

use RuntimeException;
use Webkul\AiKnowledge\Services\KnowledgeRetrievalService;
use Webkul\UnifiedInbox\Contracts\AiReplyProvider;

class ReplySuggestionService
{
    public function __construct(
        protected AiReplyProvider $provider,
        protected KnowledgeRetrievalService $knowledgeRetrieval,
    ) {}

    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * @return string[]
     */
    public function suggest($conversation, int $limit = 20): array
    {
        $messages = $conversation->messages()
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return [];
        }

        $channel = config('unified-inbox.channels.'.$conversation->channel.'.label', ucfirst((string) $conversation->channel));
        $customer = $conversation->customer_name ?: 'Customer';

        $transcript = $messages->map(function ($m) use ($customer) {
            $who = $m->direction === 'outbound' ? 'Agent' : $customer;

            return $who.': '.mb_substr(trim((string) $m->body), 0, 600);
        })->implode("\n");

        $latestCustomerMessage = $messages->last(fn ($message) => $message->direction !== 'outbound');
        $knowledge = $this->knowledgeRetrieval->search(
            (string) ($latestCustomerMessage?->body ?? $messages->last()?->body ?? ''),
            3,
        );
        $knowledgeContext = collect($knowledge)
            ->pluck('content')
            ->filter(fn ($content) => is_string($content) && trim($content) !== '')
            ->map(fn ($content) => '- '.trim($content))
            ->implode("\n");

        $userContent = "Conversation:\n{$transcript}";

        if ($knowledgeContext !== '') {
            $userContent = "Tenant knowledge (use only when relevant; do not infer facts beyond it):\n{$knowledgeContext}\n\n{$userContent}";
        }

        $raw = $this->provider->complete([
            [
                'role' => 'system',
                'content' => "You help a customer-support/sales agent reply on {$channel}. "
                    .'Read the conversation and propose 3 distinct, short replies (max ~240 characters each) the agent could send next. '
                    ."Reply in the same language and tone as the customer's latest message. Be specific to the conversation; no generic filler, "
                    .'no placeholders like [name], do not invent prices, dates or facts not in the chat or provided tenant knowledge. '
                    .'Return ONLY a JSON array of strings, e.g. ["...","...","..."].',
            ],
            ['role' => 'user', 'content' => $userContent],
        ]);

        return $this->parse($raw);
    }

    protected function parse(string $raw): array
    {
        $raw = trim(preg_replace('/^```(?:json)?|```$/m', '', $raw));

        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
        }

        if (empty($decoded) || ! is_array($decoded)) {
            throw new RuntimeException('AI returned an unexpected format.');
        }

        return collect($decoded)
            ->filter(fn ($s) => is_string($s) && trim($s) !== '')
            ->map(fn ($s) => trim($s))
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }
}
