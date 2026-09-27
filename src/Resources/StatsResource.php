<?php

declare(strict_types=1);

namespace Duva\Resources;

use DateTimeInterface;
use Duva\Config;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Models\Stats;

final readonly class StatsResource
{
    public function __construct(
        private Requester $requester,
        private Config $config,
    ) {
    }

    /**
     * Counters of the domain by period (UTC). Defaults to the last 30 days (or 24 hours, by
     * hour). At most 366 days, or 7 days by hour.
     */
    public function get(?string $granularity = null, ?DateTimeInterface $since = null, ?DateTimeInterface $until = null): Stats
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . '/stats',
            query: [
                'granularity' => $granularity,
                'since' => $since?->format(DateTimeInterface::ATOM),
                'until' => $until?->format(DateTimeInterface::ATOM),
            ],
            safeRetry: true,
        ));

        return Stats::fromArray($result->asArray());
    }
}
