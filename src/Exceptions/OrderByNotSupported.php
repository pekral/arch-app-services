<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class OrderByNotSupported extends RuntimeException
{

    public static function forDynamoDb(): self
    {
        return new self(
            'ORDER BY operations are not supported for DynamoDB. '
            . 'DynamoDB ordering works only on sort keys (range keys) in table/index definitions, '
            . 'not on arbitrary attributes.',
        );
    }

}
