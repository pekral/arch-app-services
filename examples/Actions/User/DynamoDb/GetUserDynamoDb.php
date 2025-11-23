<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\DynamoDb;

use Pekral\Arch\Examples\Models\DynamoDb\UserDynamoDb;
use Pekral\Arch\Examples\Services\User\DynamoDb\UserDynamoDbRepository;

/**
 * Example action for getting a user from DynamoDB.
 */
final readonly class GetUserDynamoDb
{

    public function __construct(private UserDynamoDbRepository $userDynamoDbRepository)
    {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function handle(array $params): UserDynamoDb
    {
        return $this->userDynamoDbRepository->getOneByParams($params);
    }

}
