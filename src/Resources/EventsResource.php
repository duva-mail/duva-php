<?php

declare(strict_types=1);

namespace Duva\Resources;

use DateTimeInterface;
use Duva\Config;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Models\Event;
use Duva\Models\EventPage;
use Duva\Pagination;
use Generator;

final readonly class EventsResource
{
    public function __construct(
        private Requester $requester,
        private Config $config,
    ) {
    }

    /** One page of delivery events, most recent first. */
    public function list(
        ?string $messageId = null,
        ?string $type = null,
        ?string $recipient = null,
        ?DateTimeInterface $since = null,
        ?int $limit = null,
        ?string $cursor = null,
    ): EventPage {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . '/events',
            query: [
                'message_id' => $messageId,
                'type' => $type,
                'recipient' => $recipient,
                'since' => $since?->format(DateTimeInterface::ATOM),
                'limit' => $limit,
                'cursor' => $cursor,
            ],
            safeRetry: true,
        ));

        return EventPage::fromArray($result->asArray());
    }

    /**
     * Every delivery event, most recent first, following `nextCursor` automatically. Returns a
     * `Generator` (nothing is fetched until you iterate).
     *
     * @return Generator<int, Event>
     */
    public function listAll(
        ?string $messageId = null,
        ?string $type = null,
        ?string $recipient = null,
        ?DateTimeInterface $since = null,
        ?int $maxItems = null,
    ): Generator {
        /** @var Generator<int, Event> $items */
        $items = Pagination::paginate(
            fn (?string $cursor): EventPage => $this->list($messageId, $type, $recipient, $since, cursor: $cursor),
            $maxItems,
        );

        return $items;
    }
}
