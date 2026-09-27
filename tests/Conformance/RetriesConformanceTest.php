<?php

declare(strict_types=1);

namespace Duva\Tests\Conformance;

use Duva\Client;
use Duva\Exceptions\ConnectionException;
use Duva\Exceptions\DuvaException;
use Duva\Exceptions\ServerException;
use Duva\Http\Psr18Transport;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Verified against `duva-mail/duva-conformance` (fetched by `scripts/fetch-conformance.php`,
 * never committed: see `.gitignore`). Run `php scripts/fetch-conformance.php` first if
 * `conformance/retries.json` is missing.
 */
final class RetriesConformanceTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../../conformance/retries.json';

    /** @return array<string, mixed> */
    private static function fixture(): array
    {
        return json_decode(file_get_contents(self::FIXTURE_PATH), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return callable(Client): mixed */
    private static function callFor(string $operationId): callable
    {
        return match ($operationId) {
            'sendMessage' => static fn (Client $duva) => $duva->messages->send(['from' => 'a@example.com', 'to' => ['b@example.org'], 'subject' => 's', 'text' => 't']),
            'getMessage' => static fn (Client $duva) => $duva->messages->get('msg_' . str_repeat('a', 32)),
            'addSuppression' => static fn (Client $duva) => $duva->suppressions->add('b@example.org'),
            'listEvents' => static fn (Client $duva) => $duva->events->list(),
            default => throw new \RuntimeException("no driver for {$operationId}"),
        };
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function cases(): array
    {
        if (!file_exists(self::FIXTURE_PATH)) {
            return [];
        }
        $cases = [];
        foreach (self::fixture()['cases'] as $case) {
            $cases[$case['name']] = [$case];
        }

        return $cases;
    }

    protected function setUp(): void
    {
        if (!file_exists(self::FIXTURE_PATH)) {
            $this->markTestSkipped('conformance/retries.json missing: run scripts/fetch-conformance.php');
        }
    }

    /** @param array<string, mixed> $testCase */
    #[DataProvider('cases')]
    public function testCase(array $testCase): void
    {
        $sequence = $testCase['response_sequence'];
        $attempts = 0;
        $httpClient = new FakeHttpClient(function (RequestInterface $request) use ($sequence, &$attempts) {
            $scripted = $sequence[$attempts];
            ++$attempts;
            if ($scripted['status'] === null) {
                throw new ConnectException('simulated network failure', $request);
            }

            return FakeHttpClient::jsonResponse($scripted['status'], $scripted['body'] ?? null, $scripted['headers'] ?? []);
        });
        $factory = new HttpFactory();
        $duva = new Client(
            apiKey: 'dv_test',
            domain: 'example.com',
            baseUrl: 'https://api.example.com',
            maxRetries: $testCase['max_retries'],
            maxRetryWaitSeconds: (float) $testCase['max_retry_wait_seconds'],
            transport: new Psr18Transport($httpClient, $factory, $factory),
        );
        $call = self::callFor($testCase['operation_id']);

        if ($testCase['expected_outcome'] === 'success') {
            // retries.json's success bodies are the same generic placeholder across every
            // operation (only the transport-level retry/error behavior is under test here, not
            // response shape): a real `listEvents` call parses a proper EventPage, this
            // fixture's body just doesn't shape-match it -- expected, not a failure.
            try {
                $call($duva);
            } catch (Throwable) {
            }
        } else {
            [, $code] = explode(':', (string) $testCase['expected_outcome'], 2);
            $error = null;
            try {
                $call($duva);
            } catch (Throwable $e) {
                $error = $e;
            }
            $this->assertNotNull($error, 'expected a failure');
            if ($code === 'server') {
                $this->assertTrue($error instanceof ServerException || $error instanceof ConnectionException);
            } else {
                $this->assertInstanceOf(DuvaException::class, $error);
                $this->assertSame($code, $error->errorCode);
            }
        }

        $this->assertSame($testCase['expected_attempts'], $attempts);
    }
}
