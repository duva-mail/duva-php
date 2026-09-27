<?php

declare(strict_types=1);

namespace Duva;

use Generator;

/**
 * Cursor pagination, shared by `events->listAll()` and `suppressions->listAll()`: follows
 * `nextCursor` until it is `null`, without ever loading every page into memory at once.
 *
 * @internal
 */
final class Pagination
{
    private function __construct()
    {
    }

    /**
     * `$fetchPage` is called with the current cursor (`null` for the first page) and must return
     * an object with a `$data` array property and a `$nextCursor` property. Returns a
     * `Generator`: nothing is fetched until the caller actually iterates (`foreach`, `iterator_to_array`...).
     *
     * Untyped on purpose (a generic object-shape callable return confuses PHPStan's inference at
     * this call boundary): each caller narrows the item type with its own `@var Generator<int,
     * T>` at the call site (see `EventsResource::listAll` / `SuppressionsResource::listAll`).
     *
     * @param callable(?string): object{data: array<int, mixed>, nextCursor: ?string} $fetchPage
     * @return Generator<int, mixed>
     */
    public static function paginate(callable $fetchPage, ?int $maxItems = null): Generator
    {
        $cursor = null;
        $yielded = 0;
        while (true) {
            $page = $fetchPage($cursor);
            foreach ($page->data as $item) {
                yield $item;
                ++$yielded;
                if ($maxItems !== null && $yielded >= $maxItems) {
                    return;
                }
            }
            if ($page->nextCursor === null) {
                return;
            }
            $cursor = $page->nextCursor;
        }
    }
}
