<?php

declare(strict_types=1);

namespace Duva\Http;

use Duva\Config;
use Duva\Exceptions\ConnectionException;
use Duva\Exceptions\DuvaException;
use Duva\Exceptions\RateLimitException;
use Duva\Exceptions\ServerException;
use Duva\Exceptions\TimeoutException;
use Throwable;

/**
 * Decides whether a failed request may be retried, exactly per
 * `docs/bibliotheques-clientes.md` section 3.5 in the `duva` repository.
 */
final class RetryPolicy
{
    private function __construct()
    {
    }

    /** Null = do not retry (re-throw); a number = wait this many seconds, then retry. */
    public static function delayFor(Throwable $error, RequestSpec $spec, int $attempt, Config $config): ?float
    {
        if ($error instanceof RateLimitException) {
            return $error->retryAfter > $config->maxRetryWaitSeconds ? null : (float) $error->retryAfter;
        }
        // A QuotaExceededException (has retryAfter too) is never retried automatically; neither
        // is any other DuvaException that isn't a ServerException (4xx: outcome already known).
        if ($error instanceof DuvaException && !$error instanceof ServerException) {
            return null;
        }
        $transient = $error instanceof ConnectionException || $error instanceof TimeoutException || $error instanceof ServerException;
        if (!$transient || !$spec->safeRetry || $attempt >= $config->maxRetries) {
            return null;
        }

        return self::backoff($attempt);
    }

    /** Exponential backoff with jitter, capped: never a fixed delay, never unbounded. */
    private static function backoff(int $attempt): float
    {
        $base = min(0.5 * (2.0 ** $attempt), 8.0);

        return ($base / 2) + (mt_rand() / mt_getrandmax()) * ($base / 2);
    }
}
