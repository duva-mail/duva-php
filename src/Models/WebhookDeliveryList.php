<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class WebhookDeliveryList
{
    /** @param array<int, WebhookDelivery> $data */
    public function __construct(
        public array $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(data: array_map(WebhookDelivery::fromArray(...), ModelSupport::mapList($data['data'] ?? [])));
    }
}
