<?php

declare(strict_types=1);

namespace Duva\Http;

/**
 * A parsed response: `data` is the parsed JSON body (or null for a 204 or an empty body),
 * `headers` a map with lower-cased keys.
 */
final readonly class RawResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public mixed $data,
        public array $headers,
    ) {
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * `data` narrowed to an associative array, for a success body known to be a JSON object.
     * Empty when the server answered something else (defends the model's own `fromArray`
     * against a malformed value rather than PHPStan-suppressing the call site).
     *
     * @return array<string, mixed>
     */
    public function asArray(): array
    {
        // @phpstan-ignore-next-line return.type (trust boundary: JSON objects decode to string-keyed arrays)
        return is_array($this->data) ? $this->data : [];
    }
}
