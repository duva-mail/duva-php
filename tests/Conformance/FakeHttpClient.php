<?php

declare(strict_types=1);

namespace Duva\Tests\Conformance;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A real PSR-18 client that never hits the network: captures the exact PSR-7 Request the
 * transport built (method, URI, headers, body) and answers with a scripted response.
 *
 * @internal
 */
final class FakeHttpClient implements ClientInterface
{
    /** @var array<int, RequestInterface> */
    public array $requests = [];

    /** @param callable(RequestInterface): ResponseInterface $handler */
    public function __construct(private readonly mixed $handler)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return ($this->handler)($request);
    }

    public static function jsonResponse(int $status, mixed $data = null, array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $data !== null ? json_encode($data) : '');
    }
}
