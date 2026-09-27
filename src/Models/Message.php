<?php

declare(strict_types=1);

namespace Duva\Models;

use DateTimeImmutable;

final readonly class Message
{
    /**
     * @param array<int, string> $tags
     * @param array<string, string> $metadata
     * @param array<int, Recipient> $recipients
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $from,
        public string $subject,
        public array $tags,
        public array $metadata,
        public Tracking $tracking,
        public DateTimeImmutable $createdAt,
        public array $recipients,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: ModelSupport::str($data['id']),
            status: ModelSupport::str($data['status']),
            from: ModelSupport::str($data['from']),
            subject: ModelSupport::str($data['subject']),
            tags: ModelSupport::strList($data['tags'] ?? []),
            metadata: ModelSupport::stringMap($data['metadata'] ?? []),
            tracking: Tracking::fromArray(ModelSupport::map($data['tracking'] ?? [])),
            createdAt: ModelSupport::time($data['created_at']),
            recipients: array_map(Recipient::fromArray(...), ModelSupport::mapList($data['recipients'] ?? [])),
        );
    }
}
