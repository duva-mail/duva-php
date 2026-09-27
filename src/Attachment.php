<?php

declare(strict_types=1);

namespace Duva;

use InvalidArgumentException;
use RuntimeException;

/**
 * Attachment helpers. The server remains the authority on the limits below (they can change):
 * these are a courtesy, so a mistake fails locally instead of after an upload.
 */
final readonly class Attachment
{
    // Mirrors `services/messages.py` in the `duva` repository at the time of writing: 10
    // attachments, 5 MB decoded in total, these extensions refused. Re-check against
    // `docs/api.md` if this drifts.
    public const MAX_ATTACHMENTS = 10;
    public const MAX_TOTAL_BYTES = 5 * 1024 * 1024;

    /** @var array<int, string> */
    private const FORBIDDEN_EXTENSIONS = ['.exe', '.bat', '.cmd', '.com', '.js', '.vbs', '.vbe', '.scr', '.msi', '.msp', '.ps1', '.jar'];

    private function __construct(
        public string $filename,
        public string $content,
        public ?string $contentType,
        public ?string $contentId,
    ) {
    }

    /** From raw bytes already in memory. */
    public static function fromBytes(string $filename, string $content, ?string $contentType = null, ?string $contentId = null): self
    {
        self::assertAllowedFilename($filename);

        return new self($filename, base64_encode($content), $contentType, $contentId);
    }

    /** Reads a file from disk. */
    public static function fromFile(string $path, ?string $contentType = null, ?string $contentId = null, ?string $filename = null): self
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Duva: could not read attachment file: {$path}");
        }

        return self::fromBytes($filename ?? basename($path), $content, $contentType, $contentId);
    }

    /**
     * @return array{filename: string, content: string, content_type: ?string, content_id: ?string}
     */
    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'content' => $this->content,
            'content_type' => $this->contentType,
            'content_id' => $this->contentId,
        ];
    }

    /**
     * Local, courtesy-only checks: at most MAX_ATTACHMENTS attachments, at most
     * MAX_TOTAL_BYTES decoded in total. Throws InvalidArgumentException when exceeded; the
     * server re-checks regardless.
     *
     * @param array<int, self> $attachments
     */
    public static function assertLimits(array $attachments): void
    {
        if (count($attachments) > self::MAX_ATTACHMENTS) {
            throw new InvalidArgumentException('Duva: at most ' . self::MAX_ATTACHMENTS . ' attachments per message');
        }
        $totalBytes = array_sum(array_map(
            static fn (self $a): int => (int) (strlen($a->content) * 3 / 4),
            $attachments,
        ));
        if ($totalBytes > self::MAX_TOTAL_BYTES) {
            throw new InvalidArgumentException("Duva: attachments are {$totalBytes} bytes decoded, over the " . self::MAX_TOTAL_BYTES . ' limit');
        }
    }

    private static function assertAllowedFilename(string $filename): void
    {
        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException("Duva: attachment filename must not contain a path: {$filename}");
        }
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext !== '' && in_array(".{$ext}", self::FORBIDDEN_EXTENSIONS, true)) {
            throw new InvalidArgumentException("Duva: executable attachments are refused: {$filename}");
        }
    }
}
