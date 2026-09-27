<?php

declare(strict_types=1);

namespace Duva\Tests\Unit;

use Duva\Address;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testReturnsTheBareAddressWithoutAName(): void
    {
        $this->assertSame('a@example.com', Address::format('a@example.com'));
    }

    public function testWrapsANameAroundTheAddress(): void
    {
        $this->assertSame('Example <a@example.com>', Address::format('a@example.com', 'Example'));
    }

    public function testQuotesAndEscapesANameContainingACommaOrAQuote(): void
    {
        $this->assertSame('"Some, \\"Name\\"" <a@example.com>', Address::format('a@example.com', 'Some, "Name"'));
    }

    public function testBuildsBothHeadersForOneClickUnsubscribeByDefault(): void
    {
        $this->assertSame(
            ['List-Unsubscribe' => '<https://example.com/u>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'],
            Address::unsubscribeHeaders(httpsUrl: 'https://example.com/u'),
        );
    }

    public function testCombinesAnHttpsLinkAndAMailtoWithoutOneClickWhenAsked(): void
    {
        $this->assertSame(
            ['List-Unsubscribe' => '<https://example.com/u>, <mailto:stop@example.com>'],
            Address::unsubscribeHeaders(httpsUrl: 'https://example.com/u', mailto: 'stop@example.com', oneClick: false),
        );
    }

    public function testAMailtoOnlyUnsubscribeNeverGetsListUnsubscribePost(): void
    {
        $this->assertSame(
            ['List-Unsubscribe' => '<mailto:stop@example.com>'],
            Address::unsubscribeHeaders(mailto: 'stop@example.com'),
        );
    }

    public function testThrowsWithoutAnyDestination(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/needs httpsUrl/');
        Address::unsubscribeHeaders();
    }

    public function testThrowsIfHttpsUrlIsNotHttps(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Address::unsubscribeHeaders(httpsUrl: 'http://example.com/u');
    }

    public function testThrowsAskingForOneClickWithoutAnHttpsLink(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Address::unsubscribeHeaders(mailto: 'stop@example.com', oneClick: true);
    }
}
