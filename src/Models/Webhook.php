<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Webhook
{
    /** @param array<int, string> $events */
    public function __construct(
        public string $id,
        public string $url,
        public array $events,
        public string $status,
        public ?string $disabledReason,
        public DateTimeImmutable $createdAt,
        /** The signing secret (`whsec_...`): present ONLY in the response that creates the endpoint. */
        public ?string $secret,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ModelSupport::str($data['id']),
            url: ModelSupport::str($data['url']),
            events: ModelSupport::strList($data['events'] ?? []),
            status: ModelSupport::str($data['status']),
            disabledReason: ModelSupport::strOrNull($data['disabled_reason'] ?? null),
            createdAt: ModelSupport::time($data['created_at']),
            secret: ModelSupport::strOrNull($data['secret'] ?? null),
        );
    }
}
