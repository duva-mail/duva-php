<?php

declare(strict_types=1);

namespace Duva\Models;

final readonly class EventPage
{
    /** @param array<int, Event> $data */
    public function __construct(
        public array $data,
        public ?string $nextCursor,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            data: array_map(Event::fromArray(...), ModelSupport::mapList($data['data'] ?? [])),
            nextCursor: ModelSupport::strOrNull($data['next_cursor'] ?? null),
        );
    }
}
