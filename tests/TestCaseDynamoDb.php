<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests;

use Aws\DynamoDb\Exception\DynamoDbException;
use BaoPham\DynamoDb\DynamoDbServiceProvider;
use BaoPham\DynamoDb\Facades\DynamoDb;
use Orchestra\Testbench\TestCase as Orchestra;
use Pekral\Arch\ArchServiceProvider;
use RuntimeException;

abstract class TestCaseDynamoDb extends Orchestra
{

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('arch.default_items_per_page', 15);
        $app['config']->set('arch.exceptions.should_not_happen', RuntimeException::class);

        $app['config']->set('dynamodb.default', 'local');
        $app['config']->set('dynamodb.connections.local', [
            'credentials' => [
                'key' => 'fakeMyKeyId',
                'secret' => 'fakeSecretAccessKey',
            ],
            'debug' => false,
            'endpoint' => 'http://localhost:8001',
            'region' => 'us-east-1',
        ]);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createDynamoDbTable();
    }

    protected function createDynamoDbTable(): void
    {
        $dynamoDb = DynamoDb::client();

        try {
            $dynamoDb->describeTable(['TableName' => 'users']);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                $this->createUsersTable($dynamoDb);
            }
        }
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ArchServiceProvider::class,
            DynamoDbServiceProvider::class,
        ];
    }

    /**
     * @param \Aws\DynamoDb\DynamoDbClient $dynamoDb
     */
    private function createUsersTable($dynamoDb): void
    {
        $dynamoDb->createTable([
            'AttributeDefinitions' => $this->getUsersTableAttributeDefinitions(),
            'GlobalSecondaryIndexes' => $this->getUsersTableGlobalSecondaryIndexes(),
            'KeySchema' => $this->getUsersTableKeySchema(),
            'ProvisionedThroughput' => $this->getUsersTableProvisionedThroughput(),
            'TableName' => 'users',
        ]);

        $dynamoDb->waitUntil('TableExists', ['TableName' => 'users']);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getUsersTableAttributeDefinitions(): array
    {
        return [
            [
                'AttributeName' => 'id',
                'AttributeType' => 'S',
            ],
            [
                'AttributeName' => 'email',
                'AttributeType' => 'S',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getUsersTableKeySchema(): array
    {
        return [
            [
                'AttributeName' => 'id',
                'KeyType' => 'HASH',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUsersTableGlobalSecondaryIndexes(): array
    {
        return [
            [
                'IndexName' => 'email-index',
                'KeySchema' => [
                    [
                        'AttributeName' => 'email',
                        'KeyType' => 'HASH',
                    ],
                ],
                'Projection' => [
                    'ProjectionType' => 'ALL',
                ],
                'ProvisionedThroughput' => $this->getUsersTableProvisionedThroughput(),
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getUsersTableProvisionedThroughput(): array
    {
        return [
            'ReadCapacityUnits' => 5,
            'WriteCapacityUnits' => 5,
        ];
    }

}
