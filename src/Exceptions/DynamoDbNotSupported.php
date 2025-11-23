<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class DynamoDbNotSupported extends RuntimeException
{

    public static function featureNotSupported(string $feature): self
    {
        return new self(sprintf('DynamoDB does not support %s. This feature is not available when using DynamoDb repositories.', $feature));
    }

    public static function groupByNotSupported(): self
    {
        return self::featureNotSupported('GROUP BY');
    }

    public static function orderByNotSupported(): self
    {
        return self::featureNotSupported('ORDER BY');
    }

    public static function eagerLoadingNotSupported(): self
    {
        return self::featureNotSupported('eager loading (with relations)');
    }

    public static function rawMassUpdateNotSupported(): self
    {
        return self::featureNotSupported('raw mass update');
    }

}
