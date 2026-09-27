<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class MessageAccepted
{
    public function __construct(
        public string $id,
        public string $status,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(id: ModelSupport::str($data['id']), status: ModelSupport::str($data['status']));
    }
}
