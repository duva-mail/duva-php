<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Suppression
{
    public function __construct(
        public string $email,
        public string $reason,
        public ?string $messageId,
        public DateTimeImmutable $createdAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            email: ModelSupport::str($data['email']),
            reason: ModelSupport::str($data['reason']),
            messageId: ModelSupport::strOrNull($data['message_id'] ?? null),
            createdAt: ModelSupport::time($data['created_at']),
        );
    }
}
