<?php

declare(strict_types=1);

namespace Duva\Exceptions;

use RuntimeException;
use Throwable;

/** No response was received at all (DNS, TLS, connection refused, connection reset...). */
class ConnectionException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
