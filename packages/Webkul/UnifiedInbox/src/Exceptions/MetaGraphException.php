<?php

namespace Webkul\UnifiedInbox\Exceptions;

use RuntimeException;

/**
 * A definitive error returned by the Meta Graph API (never contains the access token).
 */
class MetaGraphException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $graphCode = null,
        public readonly ?int $graphSubcode = null,
        public readonly ?string $graphType = null,
        public readonly ?string $fbtraceId = null,
        public readonly int $httpStatus = 0,
    ) {
        parent::__construct($message, $graphCode ?? 0);
    }

    public function context(): array
    {
        return [
            'graph_code' => $this->graphCode,
            'graph_subcode' => $this->graphSubcode,
            'graph_type' => $this->graphType,
            'fbtrace_id' => $this->fbtraceId,
            'http_status' => $this->httpStatus,
            'graph_message' => $this->getMessage(),
        ];
    }
}
