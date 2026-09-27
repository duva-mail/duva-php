<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/** 422 `invalid_request`; `fields` carries the per-field details. */
class ValidationException extends DuvaException
{
    /**
     * @param array<int, array{field: string, message: string}>|null $fields
     */
    public function __construct(int $status, string $code, string $message, string $rawBody, ?array $fields = null)
    {
        parent::__construct($status, $code, $message, $rawBody, $fields ?? []);
    }
}
