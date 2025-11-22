<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class GroupByNotSupported extends RuntimeException
{

    public static function forDynamoDb(): self
    {
        return new self('GROUP BY operations are not supported for DynamoDB. DynamoDB does not support SQL-style GROUP BY aggregations.');
    }

}
