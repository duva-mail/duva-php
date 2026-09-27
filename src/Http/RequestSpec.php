<?php

declare(strict_types=1);

namespace Duva\Http;

/** One HTTP call to make: built by a resource class, executed by a Transport. */
final readonly class RequestSpec
{
    /**
     * @param 'GET'|'POST'|'DELETE' $method
     * @param array<string, scalar|null> $query
     * @param mixed $body JSON-serializable, or null for no body.
     * @param bool $safeRetry Whether the WHOLE call may be retried after a network failure or a
     *     5xx (distinct from the per-error-code Retry-After policy, which always applies):
     *     false for a write whose outcome, after a timeout, is unknown (see
     *     `docs/bibliotheques-clientes.md` section 3.5).
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public mixed $body = null,
        public ?string $idempotencyKey = null,
        public bool $safeRetry = false,
    ) {
    }
}
