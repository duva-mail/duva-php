<?php

declare(strict_types=1);

namespace Duva\Tests\Unit;

use Duva\Exceptions\AuthenticationException;
use Duva\Exceptions\ConflictException;
use Duva\Exceptions\DuvaException;
use Duva\Exceptions\ErrorMapper;
use Duva\Exceptions\NotFoundException;
use Duva\Exceptions\PermissionException;
use Duva\Exceptions\QuotaExceededException;
use Duva\Exceptions\RateLimitException;
use Duva\Exceptions\ServerException;
use Duva\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorMapperTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: int, 2: class-string<DuvaException>}>
     */
    public static function codes(): array
    {
        return [
            'unauthorized' => ['unauthorized', 401, AuthenticationException::class],
            'not_found' => ['not_found', 404, NotFoundException::class],
            'domain_not_verified' => ['domain_not_verified', 403, PermissionException::class],
            'sending_not_allowed' => ['sending_not_allowed', 403, PermissionException::class],
            'idempotency_conflict' => ['idempotency_conflict', 409, ConflictException::class],
            'limit_reached' => ['limit_reached', 409, ConflictException::class],
            'invalid_request' => ['invalid_request', 422, ValidationException::class],
            'internal_error' => ['internal_error', 500, ServerException::class],
        ];
    }

    /** @param class-string<DuvaException> $expected */
    #[DataProvider('codes')]
    public function testMapsEachDocumentedCodeToItsClass(string $code, int $status, string $expected): void
    {
        $error = ErrorMapper::fromResponse($status, ['error' => ['code' => $code, 'message' => 'x']], '{}', null);

        $this->assertInstanceOf($expected, $error);
        $this->assertSame($code, $error->errorCode);
        $this->assertSame($status, $error->status);
    }

    public function testCarriesRetryAfterForQuotaExceededAndRateLimitedOnly(): void
    {
        $quota = ErrorMapper::fromResponse(429, ['error' => ['code' => 'quota_exceeded', 'message' => 'x']], '{}', '3600');
        $this->assertInstanceOf(QuotaExceededException::class, $quota);
        $this->assertSame(3600, $quota->retryAfter);

        $rate = ErrorMapper::fromResponse(429, ['error' => ['code' => 'rate_limited', 'message' => 'x']], '{}', '5');
        $this->assertInstanceOf(RateLimitException::class, $rate);
        $this->assertSame(5, $rate->retryAfter);
    }

    public function testCarriesFieldErrorsOnInvalidRequest(): void
    {
        $body = ['error' => ['code' => 'invalid_request', 'message' => 'x', 'fields' => [['field' => 'to[0]', 'message' => 'bad']]]];
        $error = ErrorMapper::fromResponse(422, $body, '{}', null);

        $this->assertSame([['field' => 'to[0]', 'message' => 'bad']], $error->fields);
    }

    public function testNeverCrashesOnABodyThatIsNotTheDocumentedEnvelope(): void
    {
        $error = ErrorMapper::fromResponse(502, '<html>bad gateway</html>', '<html>bad gateway</html>', null);

        $this->assertInstanceOf(ServerException::class, $error);
        $this->assertSame('http_error', $error->errorCode);
    }

    public function testBoundsTheRawBodyItKeeps(): void
    {
        $huge = str_repeat('x', 10_000);
        $error = ErrorMapper::fromResponse(500, null, $huge, null);

        $this->assertLessThanOrEqual(4096, strlen($error->rawBody));
    }

    public function testAnUnknownCodeFallsBackToServerException(): void
    {
        $error = ErrorMapper::fromResponse(599, ['error' => ['code' => 'something_new', 'message' => 'x']], '{}', null);

        $this->assertInstanceOf(ServerException::class, $error);
        $this->assertSame('something_new', $error->errorCode);
    }
}
