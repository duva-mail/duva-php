<?php

declare(strict_types=1);

namespace Duva\Resources;

use Duva\Config;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Models\Webhook;
use Duva\Models\WebhookDelivery;
use Duva\Models\WebhookDeliveryList;
use Duva\Models\WebhookList;

final readonly class WebhooksResource
{
    public function __construct(
        private Requester $requester,
        private Config $config,
    ) {
    }

    /**
     * Registers an endpoint. The response carries the signing `secret` (`whsec_...`): shown
     * ONCE, store it to verify signatures. Each call creates a DISTINCT endpoint, never retried
     * automatically.
     *
     * @param array<int, string>|null $events
     */
    public function create(string $url, ?array $events = null): Webhook
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'POST',
            path: $this->config->domainPath() . '/webhooks',
            body: ['url' => $url, 'events' => $events ?? []],
            safeRetry: false,
        ));

        return Webhook::fromArray($result->asArray());
    }

    /** @return array<int, Webhook> */
    public function list(): array
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . '/webhooks',
            safeRetry: true,
        ));

        return WebhookList::fromArray($result->asArray())->data;
    }

    public function get(string $id): Webhook
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . "/webhooks/{$id}",
            safeRetry: true,
        ));

        return Webhook::fromArray($result->asArray());
    }

    public function delete(string $id): void
    {
        $this->requester->send(new RequestSpec(
            method: 'DELETE',
            path: $this->config->domainPath() . "/webhooks/{$id}",
            safeRetry: false,
        ));
    }

    /**
     * The latest deliveries of this endpoint (`$limit`: 1 to 100, 50 by default).
     *
     * @return array<int, WebhookDelivery>
     */
    public function deliveries(string $id, ?int $limit = null): array
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . "/webhooks/{$id}/deliveries",
            query: ['limit' => $limit],
            safeRetry: true,
        ));

        return WebhookDeliveryList::fromArray($result->asArray())->data;
    }
}
