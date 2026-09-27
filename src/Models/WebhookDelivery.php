<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class WebhookDelivery
{
    public function __construct(
        public string $id,
        public string $eventId,
        public string $status,
        public int $attempts,
        public ?int $lastStatusCode,
        public ?string $lastError,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $deliveredAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ModelSupport::str($data['id']),
            eventId: ModelSupport::str($data['event_id']),
            status: ModelSupport::str($data['status']),
            attempts: ModelSupport::int($data['attempts']),
            lastStatusCode: ModelSupport::intOrNull($data['last_status_code'] ?? null),
            lastError: ModelSupport::strOrNull($data['last_error'] ?? null),
            createdAt: ModelSupport::time($data['created_at']),
            deliveredAt: ModelSupport::timeOrNull($data['delivered_at'] ?? null),
        );
    }
}
