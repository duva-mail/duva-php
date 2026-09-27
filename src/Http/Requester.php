<?php

declare(strict_types=1);

namespace Duva\Http;

use Duva\Config;
use Duva\Exceptions\ConnectionException;
use Duva\Exceptions\DuvaException;
use Duva\Exceptions\TimeoutException;

/**
 * Wraps a Transport with the retry loop (`RetryPolicy`): every resource class sends its
 * requests through this, never the raw Transport directly, so the policy actually applies.
 *
 * @internal
 */
final readonly class Requester
{
    public function __construct(
        private TransportInterface $transport,
        private Config $config,
    ) {
    }

    public function send(RequestSpec $spec): RawResponse
    {
        $attempt = 0;
        while (true) {
            try {
                return $this->transport->send($this->config, $spec);
            } catch (DuvaException|ConnectionException|TimeoutException $error) {
                $delay = RetryPolicy::delayFor($error, $spec, $attempt, $this->config);
                if ($delay === null) {
                    throw $error;
                }
                ++$attempt;
                usleep((int) ($delay * 1_000_000));
            }
        }
    }
}
