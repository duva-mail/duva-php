<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/**
 * A 429 from the per-key rate limit (unrelated to your sending quota). Retried automatically
 * when `retryAfter` fits within `maxRetryWaitSeconds`.
 */
class RateLimitException extends DuvaException
{
    /**
     * @param array<int, array{field: string, message: string}>|null $fields
     */
    public function __construct(
        int $status,
        string $code,
        string $message,
        string $rawBody,
        ?array $fields,
        public readonly int $retryAfter,
    ) {
        parent::__construct($status, $code, $message, $rawBody, $fields);
    }
}
