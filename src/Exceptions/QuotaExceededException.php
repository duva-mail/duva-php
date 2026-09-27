<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/**
 * A 429 on YOUR ACCOUNT quota (daily or monthly). `retryAfter` can be hours: never retried
 * automatically, by design (see `docs/bibliotheques-clientes.md` section 3.5).
 */
class QuotaExceededException extends DuvaException
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
