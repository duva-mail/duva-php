<?php

declare(strict_types=1);

namespace Duva;

/** The JSON body Duva sends to your webhook URL, already parsed. */
final readonly class WebhookEvent
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public string $id,
        public string $type,
        public string $domain,
        public array $data,
    ) {
    }
}
