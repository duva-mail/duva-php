<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Event
{
    /**
     * @param array<string, mixed> $detail
     * @param array<string, string> $metadata
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $messageId,
        public string $recipient,
        public DateTimeImmutable $occurredAt,
        public array $detail,
        public array $metadata,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ModelSupport::str($data['id']),
            type: ModelSupport::str($data['type']),
            messageId: ModelSupport::str($data['message_id']),
            recipient: ModelSupport::str($data['recipient']),
            occurredAt: ModelSupport::time($data['occurred_at']),
            detail: ModelSupport::map($data['detail'] ?? []),
            metadata: ModelSupport::stringMap($data['metadata'] ?? []),
        );
    }
}
