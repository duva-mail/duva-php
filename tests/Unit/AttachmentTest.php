<?php

declare(strict_types=1);

namespace Duva\Tests\Unit;

use Duva\Attachment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    public function testBase64EncodesTheBytesAndDefaultsContentTypeAndIdToNull(): void
    {
        $attachment = Attachment::fromBytes('a.txt', 'hi');

        $this->assertSame([
            'filename' => 'a.txt',
            'content' => base64_encode('hi'),
            'content_type' => null,
            'content_id' => null,
        ], $attachment->toArray());
    }

    public function testCarriesAnExplicitContentTypeAndInlineContentId(): void
    {
        $attachment = Attachment::fromBytes('logo.png', "\x01\x02\x03", contentType: 'image/png', contentId: 'logo');

        $this->assertSame('image/png', $attachment->contentType);
        $this->assertSame('logo', $attachment->contentId);
    }

    public function testRefusesAFilenameWithAPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/path/');
        Attachment::fromBytes('../a.txt', '');
    }

    public function testRefusesAnExecutableExtension(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/executable/');
        Attachment::fromBytes('virus.exe', '');
    }

    public function testAcceptsAMessageWithinTheLimits(): void
    {
        $this->expectNotToPerformAssertions();
        Attachment::assertLimits([Attachment::fromBytes('a.txt', 'hi')]);
    }

    public function testRefusesMoreThan10Attachments(): void
    {
        $attachments = array_map(static fn (int $i): Attachment => Attachment::fromBytes("a{$i}.txt", ''), range(0, 10));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/at most 10/');
        Attachment::assertLimits($attachments);
    }

    public function testRefusesMoreThan5MbDecodedInTotal(): void
    {
        $big = Attachment::fromBytes('big.bin', str_repeat("\x00", (5 * 1024 * 1024) + 1));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/limit/');
        Attachment::assertLimits([$big]);
    }
}
