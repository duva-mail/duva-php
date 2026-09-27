<?php

declare(strict_types=1);

namespace Duva;

use Duva\Http\Psr18Transport;
use Duva\Http\RawResponse;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Http\TransportInterface;
use Duva\Resources\EventsResource;
use Duva\Resources\MessagesResource;
use Duva\Resources\StatsResource;
use Duva\Resources\SuppressionsResource;
use Duva\Resources\WebhooksResource;
use RuntimeException;

/**
 * A Duva client, bound to one domain and its API key.
 *
 *   $duva = new Client(apiKey: 'dv_...', domain: 'example.com');
 *   $message = $duva->messages->send([
 *       'from' => 'Example <notifications@example.com>',
 *       'to' => ['client@example.org'],
 *       'subject' => 'Your order',
 *       'text' => 'Thank you for your order.',
 *   ]);
 */
final class Client
{
    private readonly Requester $requester;

    public readonly MessagesResource $messages;
    public readonly EventsResource $events;
    public readonly SuppressionsResource $suppressions;
    public readonly WebhooksResource $webhooks;
    public readonly StatsResource $stats;

    public function __construct(
        ?string $apiKey = null,
        ?string $domain = null,
        string $baseUrl = 'https://api.duva.ca',
        float $timeout = 10.0,
        int $maxRetries = 2,
        float $maxRetryWaitSeconds = 30.0,
        ?string $language = null,
        ?string $userAgent = null,
        ?TransportInterface $transport = null,
    ) {
        $config = Config::resolve($apiKey, $domain, $baseUrl, $timeout, $maxRetries, $maxRetryWaitSeconds, $language, $userAgent);
        $this->requester = new Requester($transport ?? self::defaultTransport(), $config);
        $this->messages = new MessagesResource($this->requester, $config);
        $this->events = new EventsResource($this->requester, $config);
        $this->suppressions = new SuppressionsResource($this->requester, $config);
        $this->webhooks = new WebhooksResource($this->requester, $config);
        $this->stats = new StatsResource($this->requester, $config);
    }

    /**
     * `GET /health`, without authentication: `['status' => 'ok']` when the service works. Throws
     * ServerException on a `503` (its database is unreachable).
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        return $this->request(new RequestSpec(method: 'GET', path: '/health', safeRetry: true))->asArray();
    }

    /** @internal */
    public function request(RequestSpec $spec): RawResponse
    {
        return $this->requester->send($spec);
    }

    private static function defaultTransport(): TransportInterface
    {
        if (!class_exists(\GuzzleHttp\Client::class) || !class_exists(\GuzzleHttp\Psr7\HttpFactory::class)) {
            throw new RuntimeException(
                'Duva: no transport given and Guzzle is not installed. Run `composer require guzzlehttp/guzzle`,'
                . ' or pass your own PSR-18 client via Client::__construct(transport: ...).',
            );
        }
        $factory = new \GuzzleHttp\Psr7\HttpFactory();

        return new Psr18Transport(new \GuzzleHttp\Client(), $factory, $factory);
    }
}
