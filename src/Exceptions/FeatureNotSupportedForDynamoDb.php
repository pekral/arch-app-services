<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class FeatureNotSupportedForDynamoDb extends RuntimeException
{

    public static function eagerLoading(): self
    {
        return new self('Eager loading (with relations) is not supported for DynamoDB. DynamoDB does not support relationships like SQL databases.');
    }

    public static function orderBy(): self
    {
        return new self('General orderBy is not supported for DynamoDB. Sorting is only available for range keys in indexes using ScanIndexForward.');
    }

    public static function groupBy(): self
    {
        return new self('GroupBy is not supported for DynamoDB. DynamoDB does not support SQL-style GROUP BY operations.');
    }

}
