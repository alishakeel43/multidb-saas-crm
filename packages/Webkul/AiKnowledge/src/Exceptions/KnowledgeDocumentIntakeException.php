<?php

namespace Webkul\AiKnowledge\Exceptions;

use RuntimeException;

class KnowledgeDocumentIntakeException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }
}
