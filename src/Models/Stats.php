<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Stats
{
    /**
     * @param array<int, StatsPeriod> $data
     * @param array<string, int> $totals The same counters, summed.
     */
    public function __construct(
        public string $granularity,
        public DateTimeImmutable $since,
        public DateTimeImmutable $until,
        public array $data,
        public array $totals,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            granularity: ModelSupport::str($data['granularity']),
            since: ModelSupport::time($data['since']),
            until: ModelSupport::time($data['until']),
            data: array_map(StatsPeriod::fromArray(...), ModelSupport::mapList($data['data'] ?? [])),
            totals: ModelSupport::intMap($data['totals'] ?? []),
        );
    }
}
