<?php

declare(strict_types = 1);

namespace Pekral\Arch\Exceptions;

use RuntimeException;

final class RelationsNotSupported extends RuntimeException
{

    public static function forDynamoDb(): self
    {
        return new self(
            'Relations (eager loading) are not supported for DynamoDB. DynamoDB is a NoSQL database and does not support SQL-style joins or relations.',
        );
    }

}
