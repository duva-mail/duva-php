<?php

declare(strict_types=1);

namespace Duva\Tests\Unit;

use Duva\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    private static function page(array $data, ?string $nextCursor): object
    {
        return (object) ['data' => $data, 'nextCursor' => $nextCursor];
    }

    public function testFollowsNextCursorUntilNullWithoutAnExtraFetch(): void
    {
        $pages = [self::page([1, 2], 'a'), self::page([3], 'b'), self::page([4, 5], null)];
        $seenCursors = [];

        $items = iterator_to_array(Pagination::paginate(function (?string $cursor) use (&$seenCursors, $pages) {
            $seenCursors[] = $cursor;

            return $pages[count($seenCursors) - 1];
        }), false);

        $this->assertSame([1, 2, 3, 4, 5], $items);
        $this->assertSame([null, 'a', 'b'], $seenCursors);
    }

    public function testASingleEmptyPageYieldsNothingAndFetchesOnlyOnce(): void
    {
        $calls = 0;
        $items = iterator_to_array(Pagination::paginate(function () use (&$calls) {
            ++$calls;

            return self::page([], null);
        }), false);

        $this->assertSame([], $items);
        $this->assertSame(1, $calls);
    }

    public function testMaxItemsStopsEarlyWithoutFetchingPagesItDoesNotNeed(): void
    {
        $calls = 0;
        $items = iterator_to_array(Pagination::paginate(function (?string $cursor) use (&$calls) {
            ++$calls;

            return self::page([1, 2, 3], $cursor === 'used' ? null : 'used');
        }, maxItems: 2), false);

        $this->assertSame([1, 2], $items);
        $this->assertSame(1, $calls); // the first page's third item is never needed
    }

    public function testReturnsALazyGeneratorNothingIsFetchedBeforeIteration(): void
    {
        $calls = 0;
        $generator = Pagination::paginate(function () use (&$calls) {
            ++$calls;

            return self::page([1], null);
        });

        $this->assertSame(0, $calls);
        $this->assertSame(1, $generator->current());
        $this->assertSame(1, $calls);
    }
}
