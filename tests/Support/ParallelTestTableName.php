<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Support;

/**
 * Isolates shared external tables (DynamoDB Local) between parallel test processes.
 */
final class ParallelTestTableName
{

    private const string PROCESS_TOKEN_KEY = 'TEST_TOKEN';

    public static function resolve(string $tableName): string
    {
        $processToken = getenv(self::PROCESS_TOKEN_KEY);

        if (!is_string($processToken) || $processToken === '') {
            return $tableName;
        }

        return sprintf('%s_%s', $tableName, $processToken);
    }

}
