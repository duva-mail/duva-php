<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class StatsPeriod
{
    public function __construct(
        public DateTimeImmutable $period,
        public int $accepted,
        public int $suppressed,
        public int $delivered,
        public int $bounced,
        public int $deferred,
        public int $expired,
        public int $complained,
        public int $opened,
        public int $clicked,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            period: ModelSupport::time($data['period']),
            accepted: ModelSupport::int($data['accepted']),
            suppressed: ModelSupport::int($data['suppressed']),
            delivered: ModelSupport::int($data['delivered']),
            bounced: ModelSupport::int($data['bounced']),
            deferred: ModelSupport::int($data['deferred']),
            expired: ModelSupport::int($data['expired']),
            complained: ModelSupport::int($data['complained']),
            opened: ModelSupport::int($data['opened']),
            clicked: ModelSupport::int($data['clicked']),
        );
    }
}
