<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class Tracking
{
    public function __construct(
        public bool $opens,
        public bool $clicks,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            opens: (bool) ($data['opens'] ?? false),
            clicks: (bool) ($data['clicks'] ?? false),
        );
    }
}
