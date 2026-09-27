<?php

declare(strict_types=1);

namespace Duva\Exceptions;

use RuntimeException;
use Throwable;

/** The request exceeded `timeout` before any response arrived. */
class TimeoutException extends RuntimeException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
