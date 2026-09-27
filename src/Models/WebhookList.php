<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class WebhookList
{
    /** @param array<int, Webhook> $data */
    public function __construct(
        public array $data,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(data: array_map(Webhook::fromArray(...), ModelSupport::mapList($data['data'] ?? [])));
    }
}
