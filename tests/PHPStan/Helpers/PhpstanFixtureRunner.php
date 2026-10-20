<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\PHPStan\Helpers;

use JsonException;
use Symfony\Component\Process\Process;

/**
 * Shared helper for running PHPStan on fixture files and collecting errors per file.
 *
 * @phpstan-type DecodedOutput array{files?: array<string, array{messages?: array<int, array{message?: string, identifier?: string}>}>}
 */
final class PhpstanFixtureRunner
{

    private const string BINARY = 'vendor/bin/phpstan';

    private const string CONFIG = 'phpstan.test.neon';

    private const string DEFAULT_ARGUMENTS_CONFIG = 'phpstan.test.default-arguments.neon';

    /**
     * @return array<string, array<string>>
     */
    public static function run(string $fixtureDir): array
    {
        return self::extractErrors(self::analyse($fixtureDir, self::CONFIG));
    }

    /**
     * The PHPStan errors reported per fixture file when the rules are registered without optional arguments.
     *
     * @return array<string, array<string>>
     */
    public static function runWithDefaultArguments(string $fixtureDir): array
    {
        return self::extractErrors(self::analyse($fixtureDir, self::DEFAULT_ARGUMENTS_CONFIG));
    }

    /**
     * The PHPStan error identifiers reported per fixture file, in report order.
     *
     * @return array<string, array<string>>
     */
    public static function runIdentifiers(string $fixtureDir): array
    {
        return self::extractIdentifiers(self::analyse($fixtureDir, self::CONFIG));
    }

    /**
     * @return DecodedOutput
     */
    private static function analyse(string $fixtureDir, string $config): array
    {
        $projectRoot = realpath(__DIR__ . '/../../../');
        $resolvedDir = realpath($fixtureDir);

        if ($projectRoot === false || $resolvedDir === false) {
            return [];
        }

        $fixtures = self::getFixturePaths($resolvedDir);

        return self::decodeOutput(self::runPhpstan($projectRoot, $config, $fixtures));
    }

    /**
     * @return array<int, string>
     */
    private static function getFixturePaths(string $dir): array
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
    private static function runPhpstan(string $projectRoot, string $config, array $fixtures): string
    {
        $binary = $projectRoot . '/' . self::BINARY;

        $process = new Process(
            [$binary, 'analyse', '--configuration=' . $projectRoot . '/' . $config, '--error-format=json', '--memory-limit=512M', ...$fixtures],
            timeout: 90,
        );
        $process->run();

        return $process->getOutput() ?: $process->getErrorOutput();
    }

    /**
     * @return DecodedOutput
     */
    private static function decodeOutput(string $rawOutput): array
    {
        $jsonStartPosition = strpos($rawOutput, '{"totals"');
        $jsonPayload = $jsonStartPosition === false
            ? $rawOutput
            : substr($rawOutput, $jsonStartPosition);

        try {
            /** @var DecodedOutput $decoded */
            $decoded = json_decode($jsonPayload, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        return $decoded;
    }

    /**
     * @param DecodedOutput $decodedOutput
     * @return array<string, array<string>>
     */
    private static function extractErrors(array $decodedOutput): array
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

    /**
     * @param DecodedOutput $decodedOutput
     * @return array<string, array<string>>
     */
    private static function extractIdentifiers(array $decodedOutput): array
    {
        $result = [];

        foreach (($decodedOutput['files'] ?? []) as $path => $fileData) {
            foreach (($fileData['messages'] ?? []) as $message) {
                if (!isset($message['identifier']) || !is_string($message['identifier'])) {
                    continue;
                }

                $result[basename($path)][] = $message['identifier'];
            }
        }

        return $result;
    }

}
