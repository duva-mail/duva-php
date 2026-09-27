<?php

declare(strict_types=1);

namespace Duva\Resources;

use Duva\Config;
use Duva\Http\Requester;
use Duva\Http\RequestSpec;
use Duva\Models\Suppression;
use Duva\Models\SuppressionPage;
use Duva\Pagination;
use Generator;

final readonly class SuppressionsResource
{
    public function __construct(
        private Requester $requester,
        private Config $config,
    ) {
    }

    /** One page of suppressed addresses, most recent first. */
    public function list(?string $reason = null, ?int $limit = null, ?string $cursor = null): SuppressionPage
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'GET',
            path: $this->config->domainPath() . '/suppressions',
            query: ['reason' => $reason, 'limit' => $limit, 'cursor' => $cursor],
            safeRetry: true,
        ));

        return SuppressionPage::fromArray($result->asArray());
    }

    /**
     * Every suppressed address, most recent first, following `nextCursor` automatically.
     *
     * @return Generator<int, Suppression>
     */
    public function listAll(?string $reason = null, ?int $maxItems = null): Generator
    {
        /** @var Generator<int, Suppression> $items */
        $items = Pagination::paginate(
            fn (?string $cursor): SuppressionPage => $this->list($reason, cursor: $cursor),
            $maxItems,
        );

        return $items;
    }

    /**
     * Adds an address by hand (reason `manual`): it receives nothing more from this domain.
     * Naturally idempotent: adding an already-suppressed address changes nothing.
     */
    public function add(string $email): Suppression
    {
        $result = $this->requester->send(new RequestSpec(
            method: 'POST',
            path: $this->config->domainPath() . '/suppressions',
            body: ['email' => $email],
            safeRetry: false,
        ));

        return Suppression::fromArray($result->asArray());
    }

    /**
     * Removes an address from the list: it may receive mail again. Throws NotFoundException if
     * it was not on the list.
     */
    public function remove(string $email): void
    {
        $this->requester->send(new RequestSpec(
            method: 'DELETE',
            path: $this->config->domainPath() . '/suppressions/' . Config::percentEncode($email),
            safeRetry: false,
        ));
    }
}
