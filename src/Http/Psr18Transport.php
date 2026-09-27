<?php

declare(strict_types=1);

namespace Duva\Http;

use Duva\Config;
use Duva\Exceptions\ConnectionException;
use Duva\Exceptions\ErrorMapper;
use Duva\Exceptions\TimeoutException;
use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Default transport: any PSR-18 client, built from injected PSR-17 factories. Falls back to
 * Guzzle (`guzzlehttp/guzzle`, a suggested, not required, dependency) when none is provided --
 * see {@see \Duva\Client::__construct}.
 */
final class Psr18Transport implements TransportInterface
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function send(Config $config, RequestSpec $spec): RawResponse
    {
        $uri = $config->baseUrl . $spec->path;
        $query = array_filter($spec->query, static fn ($value): bool => $value !== null);
        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        $request = $this->requestFactory
            ->createRequest($spec->method, $uri)
            ->withHeader('Authorization', 'Bearer ' . $config->apiKey)
            ->withHeader('User-Agent', $config->userAgentHeader());
        if ($config->language !== null) {
            $request = $request->withHeader('Accept-Language', $config->language);
        }
        if ($spec->idempotencyKey !== null) {
            $request = $request->withHeader('Idempotency-Key', $spec->idempotencyKey);
        }
        if ($spec->body !== null) {
            $json = json_encode($spec->body, JSON_THROW_ON_ERROR);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($json));
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (NetworkExceptionInterface $e) {
            // PSR-18 does not standardize a distinct timeout exception: a pragmatic, best-effort
            // read of the underlying client's own message (Guzzle's ConnectException included).
            if (str_contains(strtolower($e->getMessage()), 'time')) {
                throw new TimeoutException('Duva: request timed out', $e);
            }

            throw new ConnectionException('Duva: the request could not be sent: ' . $e->getMessage(), $e);
        }

        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }
        $rawBody = (string) $response->getBody();
        try {
            $data = $rawBody !== '' ? json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR) : null;
        } catch (JsonException) {
            $data = null;
        }
        $status = $response->getStatusCode();
        if ($status < 300) {
            return new RawResponse($status === 204 ? null : $data, $headers);
        }

        throw ErrorMapper::fromResponse($status, $data, $rawBody, $headers['retry-after'] ?? null);
    }
}
