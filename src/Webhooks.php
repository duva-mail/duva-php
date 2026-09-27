<?php

declare(strict_types=1);

namespace Duva;

use Duva\Exceptions\WebhookSignatureException;
use Duva\Models\ModelSupport;
use JsonException;

/**
 * Webhook signature verification, "Standard Webhooks" format (see `docs/api.md` "Webhooks" in
 * the `duva` repository). Duva SENDS webhooks; this class is for VERIFYING them on your side.
 */
final class Webhooks
{
    private const SECRET_PREFIX = 'whsec_';
    private const DEFAULT_TOLERANCE_SECONDS = 300;

    private function __construct()
    {
    }

    /**
     * Verifies a webhook request. `$secrets` accepts one secret or an array (for key rotation:
     * while both the old and the new secret are active, a webhook signed with either must
     * verify).
     *
     * `$headers` is looked up case-insensitively (whatever your framework hands you is not
     * guaranteed to have lower-case keys). `$rawBody` must be the EXACT bytes Duva sent:
     * re-encoding a parsed-then-re-serialized JSON body changes its bytes and invalidates every
     * signature.
     *
     * `$now`: the current instant as a Unix timestamp in seconds. Defaults to `time()`; override
     * only in your OWN tests (see {@see self::sign} and the fixtures of
     * `duva-mail/duva-conformance`, which document the exact instant each vector was signed at).
     *
     * @param string|array<int, string> $secrets
     * @param array<string, string> $headers
     */
    public static function verifySignature(
        string|array $secrets,
        array $headers,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        $eventId = self::header($headers, 'webhook-id');
        $timestamp = self::header($headers, 'webhook-timestamp');
        $signatureHeader = self::header($headers, 'webhook-signature');
        if ($eventId === null || $timestamp === null || $signatureHeader === null || $signatureHeader === '') {
            return false;
        }
        if (!ctype_digit($timestamp)) {
            return false;
        }
        $at = (int) $timestamp;
        $current = $now ?? time();
        if (abs($current - $at) > $toleranceSeconds) {
            return false;
        }

        $secretList = is_array($secrets) ? $secrets : [$secrets];
        $expected = [];
        foreach ($secretList as $secret) {
            try {
                $expected[] = self::expectedSignature($secret, $eventId, $timestamp, $rawBody);
            } catch (WebhookSignatureException) {
                return false;
            }
        }

        // `webhook-signature` may carry several space-separated `v1,<signature>` entries (Duva
        // sends one; a sender that itself rotates its OWN signing key mid-flight could send
        // more): any match against any of your active secrets is accepted.
        foreach (explode(' ', $signatureHeader) as $part) {
            [$version, $signature] = array_pad(explode(',', $part, 2), 2, '');
            if ($version !== 'v1' || $signature === '') {
                continue;
            }
            foreach ($expected as $candidate) {
                if (hash_equals($candidate, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@see self::verifySignature}, then parses the body: throws WebhookSignatureException on a
     * bad signature rather than returning a boolean, for call sites that want to throw on
     * failure. Never includes the secret or the raw body in the exception.
     *
     * @param string|array<int, string> $secrets
     * @param array<string, string> $headers
     */
    public static function constructEvent(
        string|array $secrets,
        array $headers,
        string $rawBody,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): WebhookEvent {
        if (!self::verifySignature($secrets, $headers, $rawBody, $toleranceSeconds, $now)) {
            throw new WebhookSignatureException('webhook signature verification failed');
        }

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new WebhookSignatureException('webhook body is not valid JSON', $e);
        }

        return new WebhookEvent(
            id: ModelSupport::str($parsed['id']),
            type: ModelSupport::str($parsed['type']),
            domain: ModelSupport::str($parsed['domain']),
            data: ModelSupport::map($parsed['data'] ?? []),
        );
    }

    /**
     * Builds a validly signed request FOR YOUR OWN TESTS: the headers a real Duva webhook
     * delivery would carry for `$body`, signed with `$secret` as of `$timestamp` (Unix seconds;
     * defaults to now). Never used by the library itself to send anything: Duva is the only real
     * sender.
     *
     * @return array<string, string>
     */
    public static function sign(string $secret, string $eventId, string $body, ?int $timestamp = null): array
    {
        $at = $timestamp ?? time();
        $ts = (string) $at;
        $signature = self::expectedSignature($secret, $eventId, $ts, $body);

        return [
            'content-type' => 'application/json',
            'webhook-id' => $eventId,
            'webhook-timestamp' => $ts,
            'webhook-signature' => "v1,{$signature}",
        ];
    }

    /** @param array<string, string> $headers */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === $name) {
                return $value;
            }
        }

        return null;
    }

    private static function decodeSecret(string $secret): string
    {
        if (!str_starts_with($secret, self::SECRET_PREFIX)) {
            throw new WebhookSignatureException('a Duva webhook secret starts with whsec_');
        }
        $decoded = base64_decode(substr($secret, strlen(self::SECRET_PREFIX)), true);
        if ($decoded === false) {
            throw new WebhookSignatureException('a Duva webhook secret starts with whsec_');
        }

        return $decoded;
    }

    private static function expectedSignature(string $secret, string $eventId, string $timestamp, string $rawBody): string
    {
        $key = self::decodeSecret($secret);

        return base64_encode(hash_hmac('sha256', "{$eventId}.{$timestamp}.{$rawBody}", $key, true));
    }
}
