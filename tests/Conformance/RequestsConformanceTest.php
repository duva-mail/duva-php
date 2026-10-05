<?php

declare(strict_types=1);

namespace Duva\Tests\Conformance;

use Duva\Client;
use Duva\Http\Psr18Transport;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Verified against `duva-mail/duva-conformance` (fetched by `scripts/fetch-conformance.php`,
 * never committed: see `.gitignore`). Run `php scripts/fetch-conformance.php` first if
 * `conformance/requests.json` is missing.
 */
final class RequestsConformanceTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../../conformance/requests.json';

    /** @return array<string, mixed> */
    private static function fixture(): array
    {
        return json_decode(file_get_contents(self::FIXTURE_PATH), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return callable(Client, array<string, mixed>): mixed */
    private static function callFor(string $operationId): callable
    {
        return match ($operationId) {
            'sendMessage' => static fn (Client $duva, array $input) => $duva->messages->send([
                'from' => $input['from_'],
                'to' => $input['to'],
                'cc' => $input['cc'] ?? null,
                'bcc' => $input['bcc'] ?? null,
                'subject' => $input['subject'],
                'text' => $input['text'] ?? null,
                'tags' => $input['tags'] ?? null,
                'metadata' => $input['metadata'] ?? null,
                'idempotency_key' => $input['idempotency_key'] ?? null,
            ]),
            'addSuppression' => static fn (Client $duva, array $input) => $duva->suppressions->add($input['email']),
            'createWebhook' => static fn (Client $duva, array $input) => $duva->webhooks->create($input['url'], $input['events'] ?? null),
            'getMessage' => static fn (Client $duva, array $input) => $duva->messages->get($input['id']),
            'removeSuppression' => static fn (Client $duva, array $input) => $duva->suppressions->remove($input['email']),
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
            $cases[$case['operation_id']] = [$case];
        }

        return $cases;
    }

    protected function setUp(): void
    {
        if (!file_exists(self::FIXTURE_PATH)) {
            $this->markTestSkipped('conformance/requests.json missing: run scripts/fetch-conformance.php');
        }
    }

    /** @param array<string, mixed> $testCase */
    #[DataProvider('cases')]
    public function testCase(array $testCase): void
    {
        // A generic stub covering every model's required fields at once (Message, Suppression,
        // Webhook...): only the request that was SENT matters to this test (see below), so a
        // superset body avoids "undefined array key" noise from whichever model happens to
        // parse it, rather than one body per operation.
        $stub = [
            'id' => 'x', 'status' => 'queued', 'data' => [],
            'email' => 'x@example.com', 'reason' => 'manual', 'created_at' => '2026-01-01T00:00:00Z',
            'url' => 'https://example.org/hook', 'events' => [],
            'from' => 'x@example.com', 'subject' => 's', 'tags' => [], 'metadata' => [],
            'tracking' => ['opens' => false, 'clicks' => false], 'recipients' => [],
        ];
        $httpClient = new FakeHttpClient(static fn (RequestInterface $request) => FakeHttpClient::jsonResponse(200, $stub));
        $factory = new HttpFactory();
        $duva = new Client(
            apiKey: $testCase['input']['api_key'],
            domain: $testCase['input']['domain'],
            baseUrl: 'https://api.example.com',
            transport: new Psr18Transport($httpClient, $factory, $factory),
        );

        // See FakeHttpClient's docstring: only the request that was SENT matters here; a
        // downstream model-parsing mismatch against this generic fake body is not this test's
        // concern.
        try {
            (self::callFor($testCase['operation_id']))($duva, $testCase['input']);
        } catch (\Throwable) {
        }

        $this->assertCount(1, $httpClient->requests);
        $request = $httpClient->requests[0];
        $expected = $testCase['expected_request'];
        $this->assertSame($expected['method'], $request->getMethod());
        $this->assertSame($expected['path'], $request->getUri()->getPath());
        foreach ($expected['headers'] as $name => $value) {
            $this->assertSame($value, $request->getHeaderLine($name), $name);
        }
        $body = (string) $request->getBody();
        if ($expected['body'] === null) {
            $this->assertSame('', $body);
        } else {
            $this->assertSame($expected['body'], json_decode($body, true, flags: JSON_THROW_ON_ERROR));
        }
    }

    public function testCoversEveryOperationTheGeneratorDeclares(): void
    {
        $missing = [];
        foreach (self::fixture()['cases'] as $case) {
            try {
                self::callFor($case['operation_id']);
            } catch (\RuntimeException) {
                $missing[] = $case['operation_id'];
            }
        }
        $this->assertSame([], $missing);
    }
}
