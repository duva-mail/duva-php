<?php

declare(strict_types=1);

namespace Duva\Tests\Conformance;

use Duva\Webhooks;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Verified against `duva-mail/duva-conformance` (fetched by `scripts/fetch-conformance.php`,
 * never committed: see `.gitignore`). Run `php scripts/fetch-conformance.php` first if
 * `conformance/webhooks.json` is missing.
 */
final class WebhooksConformanceTest extends TestCase
{
    private const FIXTURE_PATH = __DIR__ . '/../../conformance/webhooks.json';

    /** @return array<string, mixed> */
    private static function fixture(): array
    {
        return json_decode(file_get_contents(self::FIXTURE_PATH), true, flags: JSON_THROW_ON_ERROR);
    }

    private static function now(array $fixture): int
    {
        return (new \DateTimeImmutable($fixture['reference_now']))->getTimestamp();
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function nonRotationCases(): array
    {
        if (!file_exists(self::FIXTURE_PATH)) {
            return [];
        }
        $cases = [];
        foreach (self::fixture()['cases'] as $case) {
            if ($case['category'] !== 'rotation') {
                $cases[$case['name']] = [$case];
            }
        }

        return $cases;
    }

    protected function setUp(): void
    {
        if (!file_exists(self::FIXTURE_PATH)) {
            $this->markTestSkipped('conformance/webhooks.json missing: run scripts/fetch-conformance.php');
        }
    }

    /** @param array<string, mixed> $testCase */
    #[DataProvider('nonRotationCases')]
    public function testCase(array $testCase): void
    {
        $fixture = self::fixture();
        // Always its OWN secret (the fixture's canonical one); `signed_with` is informational
        // only: it's exactly what a `wrong_secret` case distinguishes.
        $got = Webhooks::verifySignature(
            $fixture['secret'],
            $testCase['headers'],
            $testCase['body'],
            toleranceSeconds: $fixture['tolerance_seconds'],
            now: self::now($fixture),
        );

        $this->assertSame($testCase['expect'], $got);
    }

    public function testCoversEveryNonRotationCase(): void
    {
        $fixture = self::fixture();
        $this->assertCount(count($fixture['cases']) - 1, self::nonRotationCases());
    }

    public function testARotationVectorVerifiesAgainstAListOfActiveSecrets(): void
    {
        $fixture = self::fixture();
        $rotation = null;
        foreach ($fixture['cases'] as $case) {
            if ($case['category'] === 'rotation') {
                $rotation = $case;
            }
        }
        $this->assertNotNull($rotation);

        // The active set during rotation: the current secret (`other_valid_secret`, == the
        // top-level `secret`) AND the older one that actually signed this webhook (`signed_with`).
        $secrets = [$rotation['other_valid_secret'], $rotation['signed_with']];
        $got = Webhooks::verifySignature($secrets, $rotation['headers'], $rotation['body'], now: self::now($fixture));

        $this->assertSame($rotation['expect'], $got);
    }

    public function testTheCurrentSecretAloneIsNotEnough(): void
    {
        $fixture = self::fixture();
        $rotation = null;
        foreach ($fixture['cases'] as $case) {
            if ($case['category'] === 'rotation') {
                $rotation = $case;
            }
        }
        $now = self::now($fixture);

        $withCurrentOnly = Webhooks::verifySignature($rotation['other_valid_secret'], $rotation['headers'], $rotation['body'], now: $now);
        $this->assertFalse($withCurrentOnly);

        // The point: a verifier must try it too, it doesn't know which one signed.
        $withTheSignerAlone = Webhooks::verifySignature($rotation['signed_with'], $rotation['headers'], $rotation['body'], now: $now);
        $this->assertTrue($withTheSignerAlone);
    }
}
