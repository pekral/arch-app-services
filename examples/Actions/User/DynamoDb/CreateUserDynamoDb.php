<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbModelManager;

/**
 * Example action for creating a user in DynamoDB.
 */
final readonly class CreateUserDynamoDb
{

    public function __construct(private UserDynamoDbModelManager $userDynamoDbModelManager)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function handle(array $data): UserDynamoDb
    {
        return $this->userDynamoDbModelManager->create($data);
    }

}
