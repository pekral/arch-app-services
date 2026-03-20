<?php

declare(strict_types = 1);

use Symfony\Component\Process\Process;

/**
 * Run PHPStan on all ActionInvokeMethodRule fixtures at once and return a map
 * of basename → list of error messages. Each fixture file is analysed in a
 * single PHPStan invocation to avoid multiple startup costs.
 *
 * @return array<string, array<string>>
 */
function runActionInvokeRuleFixtures(): array
{
    $binary = __DIR__ . '/../../../vendor/bin/phpstan';
    $config = __DIR__ . '/../../../phpstan.test.neon';
    $dir = resolveActionInvokeFixtureDir();

    if ($dir === null) {
        return [];
    }

    $fixtures = getActionInvokeFixturePaths($dir);
    $rawOutput = runPhpstanForActionInvokeFixtures($binary, $config, $fixtures);
    $decodedOutput = decodePhpstanOutput($rawOutput);

    return extractFixtureErrors($decodedOutput);
}

function resolveActionInvokeFixtureDir(): ?string
{
    $dir = realpath(__DIR__ . '/../../../tests/fixtures/PHPStan/ActionInvokeMethodRule');

    if ($dir === false) {
        return null;
    }

    return $dir;
}

/**
 * @return array<int, string>
 */
function getActionInvokeFixturePaths(string $dir): array
{
    $fixtureFiles = array_values(array_filter(
        scandir($dir),
        static fn (string $fixture): bool => str_ends_with($fixture, '.php'),
    ));

    return array_map(
        static fn (string $fixture): string => $dir . '/' . $fixture,
        $fixtureFiles,
    );
}

/**
 * @param array<int, string> $fixtures
 */
function runPhpstanForActionInvokeFixtures(string $binary, string $config, array $fixtures): string
{
    $process = new Process(
        [$binary, 'analyse', '--configuration=' . $config, '--error-format=json', '--memory-limit=512M', ...$fixtures],
        timeout: 90,
    );
    $process->run();

    return $process->getOutput() ?: $process->getErrorOutput();
}

/**
 * @return array{files?: array<string, array{messages?: array<int, array{message?: string}>}>}
 */
function decodePhpstanOutput(string $rawOutput): array
{
    $jsonStartPosition = strpos($rawOutput, '{"totals"');
    $jsonPayload = $jsonStartPosition === false
        ? $rawOutput
        : substr($rawOutput, $jsonStartPosition);

    try {
        /** @var array{files?: array<string, array{messages?: array<int, array{message?: string}>}>} $decoded */
        $decoded = json_decode($jsonPayload, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return [];
    }

    return $decoded;
}

/**
 * @param array{files?: array<string, array{messages?: array<int, array{message?: string}>}>} $decodedOutput
 * @return array<string, array<string>>
 */
function extractFixtureErrors(array $decodedOutput): array
{
    $result = [];

    foreach (($decodedOutput['files'] ?? []) as $path => $fileData) {
        $fixtureName = basename($path);

        foreach (($fileData['messages'] ?? []) as $message) {
            if (!isset($message['message']) || !is_string($message['message'])) {
                continue;
            }

            $result[$fixtureName][] = $message['message'];
        }
    }

    return $result;
}

test('ActionInvokeMethodRule enforces final, readonly, invoke-only, and explicit return type', function (): void {
    $errors = runActionInvokeRuleFixtures();

    // Valid action — no rule errors expected
    expect($errors)->not->toHaveKey('ValidFinalReadonlyAction.php');

    // Missing final modifier
    expect($errors['NotFinalAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\NotFinalAction" must be declared as "final".',
    );

    // Missing readonly modifier
    expect($errors['NotReadonlyAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\NotReadonlyAction" must be declared as "readonly".',
    );

    // Missing return type on __invoke()
    expect($errors['MissingReturnTypeAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\MissingReturnTypeAction" '
        . 'must declare an explicit return type on "__invoke()".',
    );

    // No public methods at all
    expect($errors['NoPublicMethodAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\NoPublicMethodAction" '
        . 'must declare a public "__invoke()" method and no other public methods.',
    );

    // Public method named other than __invoke
    expect($errors['WrongMethodNameAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\WrongMethodNameAction" '
        . 'must use only public "__invoke()" as its entry point, "handle()" given.',
    );

    // More than one public method
    expect($errors['MultiplePublicMethodsAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\MultiplePublicMethodsAction" '
        . 'must not declare public methods other than "__invoke()", but found: __invoke, extra.',
    );
});
