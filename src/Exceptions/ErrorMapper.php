<?php

declare(strict_types=1);

namespace Duva\Exceptions;

/**
 * Builds the right {@see DuvaException} subclass from a parsed response body, or a generic
 * {@see ServerException} when the body does not match the documented envelope (a proxy error
 * page, for instance): never throws itself.
 */
final class ErrorMapper
{
    /** @var array<string, class-string<DuvaException>> */
    private const CODES = [
        'unauthorized' => AuthenticationException::class,
        'not_found' => NotFoundException::class,
        'domain_not_verified' => PermissionException::class,
        'sending_not_allowed' => PermissionException::class,
        'idempotency_conflict' => ConflictException::class,
        'limit_reached' => ConflictException::class,
        'payload_too_large' => PayloadTooLargeException::class,
        'invalid_request' => ValidationException::class,
        'internal_error' => ServerException::class,
        'method_not_allowed' => ServerException::class,
        'http_error' => ServerException::class,
    ];

    private function __construct()
    {
    }

    public static function fromResponse(int $status, mixed $parsedBody, string $rawBody, ?string $retryAfterHeader): DuvaException
    {
        [$code, $message, $fields] = self::asErrorBody($parsedBody);
        $retryAfter = $retryAfterHeader !== null ? (int) $retryAfterHeader : 0;

        return match ($code) {
            'quota_exceeded' => new QuotaExceededException($status, $code, $message, $rawBody, $fields, $retryAfter),
            'rate_limited' => new RateLimitException($status, $code, $message, $rawBody, $fields, $retryAfter),
            default => new (self::CODES[$code] ?? ServerException::class)($status, $code, $message, $rawBody, $fields),
        };
    }

    /**
     * @return array{0: string, 1: string, 2: array<int, array{field: string, message: string}>|null}
     */
    private static function asErrorBody(mixed $value): array
    {
        if (
            is_array($value)
            && isset($value['error']) && is_array($value['error'])
            && isset($value['error']['code']) && is_string($value['error']['code'])
        ) {
            $error = $value['error'];

            // @phpstan-ignore-next-line return.type (trust boundary: our own server's error envelope)
            return [$error['code'], (string) ($error['message'] ?? ''), $error['fields'] ?? null];
        }

        return ['http_error', 'Duva answered with an unexpected body.', null];
    }
}
