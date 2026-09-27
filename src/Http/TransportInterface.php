<?php

declare(strict_types=1);

namespace Duva\Http;

use Duva\Config;
use Duva\Exceptions\ConnectionException;
use Duva\Exceptions\DuvaException;
use Duva\Exceptions\TimeoutException;

/**
 * Executes one {@see RequestSpec} and returns a {@see RawResponse}, or throws a
 * {@see DuvaException} subclass / {@see ConnectionException} / {@see TimeoutException}. Any
 * object implementing this can be injected instead of the default {@see Psr18Transport} -- for
 * your own tests (see {@see TestTransport}) or to plug in a different PSR-18 client.
 */
interface TransportInterface
{
    public function send(Config $config, RequestSpec $spec): RawResponse;
}
