<?php

declare(strict_types=1);

namespace Duva;

use InvalidArgumentException;

/** Resolved, validated client configuration. */
final readonly class Config
{
    private function __construct(
        public string $apiKey,
        public string $domain,
        public string $baseUrl,
        public float $timeout,
        public int $maxRetries,
        public float $maxRetryWaitSeconds,
        public ?string $language,
        public ?string $userAgent,
    ) {
    }

    public static function resolve(
        ?string $apiKey = null,
        ?string $domain = null,
        string $baseUrl = 'https://api.duva.ca',
        float $timeout = 10.0,
        int $maxRetries = 2,
        float $maxRetryWaitSeconds = 30.0,
        ?string $language = null,
        ?string $userAgent = null,
    ): self {
        $envKey = getenv('DUVA_API_KEY');
        $envDomain = getenv('DUVA_DOMAIN');
        $resolvedKey = $apiKey ?? ($envKey !== false ? $envKey : null);
        $resolvedDomain = $domain ?? ($envDomain !== false ? $envDomain : null);
        if ($resolvedKey === null || $resolvedKey === '') {
            throw new InvalidArgumentException('Duva: an API key is required ($apiKey or DUVA_API_KEY)');
        }
        if ($resolvedDomain === null || $resolvedDomain === '') {
            throw new InvalidArgumentException('Duva: a domain is required ($domain or DUVA_DOMAIN)');
        }
        $cleanBaseUrl = rtrim($baseUrl, '/');
        if (!str_starts_with($cleanBaseUrl, 'https://') && !str_contains($cleanBaseUrl, 'localhost')) {
            throw new InvalidArgumentException('Duva: baseUrl must be https:// (http://localhost is allowed for tests)');
        }

        return new self($resolvedKey, $resolvedDomain, $cleanBaseUrl, $timeout, $maxRetries, $maxRetryWaitSeconds, $language, $userAgent);
    }

    /** The path prefix for this client's domain (`/v1/<domain>`). */
    public function domainPath(): string
    {
        return '/v1/' . self::percentEncode($this->domain);
    }

    public function userAgentHeader(): string
    {
        $extra = $this->userAgent !== null ? ' ' . $this->userAgent : '';

        return 'duva-php/' . Version::CURRENT . ' PHP/' . PHP_VERSION . $extra;
    }

    /**
     * RFC 3986 percent-encoding with no "safe" characters (mirrors Python's
     * `urllib.parse.quote(s, safe="")`): every octet outside unreserved (`A-Za-z0-9-_.~`) is
     * escaped, including `/`.
     */
    public static function percentEncode(string $value): string
    {
        return rawurlencode($value);
    }
}
