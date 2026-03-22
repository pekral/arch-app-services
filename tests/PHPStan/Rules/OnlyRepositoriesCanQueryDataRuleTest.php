<?php

declare(strict_types = 1);

use Symfony\Component\Process\Process;

/**
 * Run PHPStan on all OnlyRepositoriesCanQueryDataRule fixtures at once and return a map
 * of basename → list of error messages.
 *
 * @return array<string, array<string>>
 */
function runOnlyRepositoriesCanQueryDataRuleFixtures(): array
{
    $binary = __DIR__ . '/../../../vendor/bin/phpstan';
    $config = __DIR__ . '/../../../phpstan.test.neon';
    $dir = resolveOnlyRepositoriesFixtureDir();

    if ($dir === null) {
        return [];
    }

    $fixtures = getOnlyRepositoriesFixturePaths($dir);
    $rawOutput = runPhpstanForOnlyRepositoriesFixtures($binary, $config, $fixtures);
    $decodedOutput = decodeOnlyRepositoriesPhpstanOutput($rawOutput);

    return extractOnlyRepositoriesFixtureErrors($decodedOutput);
}

function resolveOnlyRepositoriesFixtureDir(): ?string
{
    $dir = realpath(__DIR__ . '/../../../tests/fixtures/PHPStan/OnlyRepositoriesCanQueryDataRule');

    if ($dir === false) {
        return null;
    }

    return $dir;
}

/**
 * @return array<int, string>
 */
function getOnlyRepositoriesFixturePaths(string $dir): array
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
function runPhpstanForOnlyRepositoriesFixtures(string $binary, string $config, array $fixtures): string
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
function decodeOnlyRepositoriesPhpstanOutput(string $rawOutput): array
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
function extractOnlyRepositoriesFixtureErrors(array $decodedOutput): array
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

test('OnlyRepositoriesCanQueryDataRule blocks query methods outside allowed classes', function (): void {
    $errors = runOnlyRepositoriesCanQueryDataRuleFixtures();
    $prefix = 'Eloquent query method "%s()" can only be called in Repository, '
        . 'ModelManager, or ModelService classes. Found in: '
        . 'Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule\\';

    expect($errors)->toHaveKey('ControllerWithQuery.php');

    $controllerErrors = $errors['ControllerWithQuery.php'];
    expect($controllerErrors)
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'where'))
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'orderBy'))
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'get'));

    expect($errors)->toHaveKey('ControllerWithStaticQuery.php');
    expect($errors['ControllerWithStaticQuery.php'])
        ->toContain(sprintf($prefix . 'ControllerWithStaticQuery', 'find'));

    expect($errors)->toHaveKey('ServiceWithQuery.php');
    expect($errors['ServiceWithQuery.php'])
        ->toContain(sprintf($prefix . 'ServiceWithQuery', 'whereIn'));

    expect($errors)->not->toHaveKey('ValidRepository.php');
    expect($errors)->not->toHaveKey('ValidModelManager.php');
    expect($errors)->not->toHaveKey('ValidModelService.php');
    expect($errors)->not->toHaveKey('ClassWithSafeBuilderOnly.php');
});
