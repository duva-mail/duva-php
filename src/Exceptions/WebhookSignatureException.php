<?php

declare(strict_types=1);

namespace Duva\Exceptions;

use RuntimeException;
use Throwable;

/** A webhook signature failed to verify: never carries the secret or the raw body. */
class WebhookSignatureException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
