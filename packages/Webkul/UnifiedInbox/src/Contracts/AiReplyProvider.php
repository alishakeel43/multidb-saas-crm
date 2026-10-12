<?php

namespace Webkul\UnifiedInbox\Contracts;

interface AiReplyProvider
{
    /**
     * Whether the provider has enough configuration (key + model) to be called.
     */
    public function isConfigured(): bool;

    /**
     * Send chat-style messages and return the raw assistant text.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function complete(array $messages): string;
}
