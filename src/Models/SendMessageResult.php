<?php

declare(strict_types=1);

namespace Duva\Models;

/** What `messages->send()` returns: the accepted message plus the idempotency outcome. */
final readonly class SendMessageResult
{
    public function __construct(
        public string $id,
        public string $status,
        public bool $replayed,
        public ?string $location,
    ) {
    }
}
