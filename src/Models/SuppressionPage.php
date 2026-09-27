<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class SuppressionPage
{
    /** @param array<int, Suppression> $data */
    public function __construct(
        public array $data,
        public ?string $nextCursor,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(Suppression::fromArray(...), ModelSupport::mapList($data['data'] ?? [])),
            nextCursor: ModelSupport::strOrNull($data['next_cursor'] ?? null),
        );
    }
}
