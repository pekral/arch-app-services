<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\PHPStan\Helpers;

use JsonException;
use Symfony\Component\Process\Process;

/**
 * Shared helper for running PHPStan on fixture files and collecting errors per file.
 */
final class PhpstanFixtureRunner
{

    private const string BINARY = 'vendor/bin/phpstan';

    private const string CONFIG = 'phpstan.test.neon';

    /**
     * @return array<string, array<string>>
     */
    public static function run(string $fixtureDir): array
    {
        $projectRoot = realpath(__DIR__ . '/../../../');
        $resolvedDir = realpath($fixtureDir);

        if ($projectRoot === false || $resolvedDir === false) {
            return [];
        }

        $fixtures = self::getFixturePaths($resolvedDir);
        $rawOutput = self::runPhpstan($projectRoot, $fixtures);

        return self::extractErrors(self::decodeOutput($rawOutput));
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
    private static function runPhpstan(string $projectRoot, array $fixtures): string
    {
        $binary = $projectRoot . '/' . self::BINARY;
        $config = $projectRoot . '/' . self::CONFIG;

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
    private static function decodeOutput(string $rawOutput): array
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

}
