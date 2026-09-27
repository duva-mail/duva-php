<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Recipient
{
    public function __construct(
        public string $email,
        public string $status,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            email: ModelSupport::str($data['email']),
            status: ModelSupport::str($data['status']),
            updatedAt: ModelSupport::time($data['updated_at']),
        );
    }
}
