<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use BaoPham\DynamoDb\DynamoDb\DynamoDbManager;
use BaoPham\DynamoDb\DynamoDbClientInterface;
use BaoPham\DynamoDb\DynamoDbClientService;
use BaoPham\DynamoDb\DynamoDbModel;
use BaoPham\DynamoDb\EmptyAttributeFilter;
use Orchestra\Testbench\TestCase as Orchestra;
use Pekral\Arch\ArchServiceProvider;
use RuntimeException;

abstract class DynamoDbTestCase extends Orchestra
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureDynamoDb();
        $this->setupDynamoDbModel();
        $this->createDynamoDbTable();
    }

    protected function setupDynamoDbModel(): void
    {
        if (DynamoDbModel::getDynamoDbClientService() === null) {
            $marshalerOptions = [
                'nullify_invalid' => true,
            ];
            $marshaler = new Marshaler($marshalerOptions);
            $filter = new EmptyAttributeFilter();
            $clientService = new DynamoDbClientService($marshaler, $filter);
            $this->app->singleton(DynamoDbClientInterface::class, fn (): DynamoDbClientService => $clientService);
            $this->app->singleton(
                'dynamodb',
                fn (): DynamoDbManager => new DynamoDbManager($this->app->make(DynamoDbClientInterface::class)),
            );
            DynamoDbModel::setDynamoDbClientService($clientService);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDynamoDbTable();

        parent::tearDown();
    }

    protected function configureDynamoDb(): void
    {
        $app = $this->app;

        $app['config']->set('aws.region', env('AWS_DEFAULT_REGION', 'us-east-1'));
        $app['config']->set('aws.credentials', [
            'key' => env('AWS_ACCESS_KEY_ID', 'fakeMyKeyId'),
            'secret' => env('AWS_SECRET_ACCESS_KEY', 'fakeSecretAccessKey'),
        ]);

        $endpoint = env('DYNAMODB_ENDPOINT', 'http://localhost:8021');
        $app['config']->set('services.dynamodb.endpoint', $endpoint);

        $app['config']->set('dynamodb', [
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', 'fakeMyKeyId'),
                'secret' => env('AWS_SECRET_ACCESS_KEY', 'fakeSecretAccessKey'),
            ],
            'endpoint' => $endpoint,
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);
    }

    protected function createDynamoDbTable(): void
    {
        $client = $this->getDynamoDbClient();

        try {
            $client->createTable([
                'AttributeDefinitions' => [
                    [
                        'AttributeName' => 'id',
                        'AttributeType' => 'S',
                    ],
                ],
                'BillingMode' => 'PAY_PER_REQUEST',
                'KeySchema' => [
                    [
                        'AttributeName' => 'id',
                        'KeyType' => 'HASH',
                    ],
                ],
                'TableName' => 'users',
            ]);

            $client->waitUntil('TableExists', [
                'TableName' => 'users',
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() !== 'ResourceInUseException') {
                throw $e;
            }
        }
    }

    protected function deleteDynamoDbTable(): void
    {
        $client = $this->getDynamoDbClient();

        try {
            $client->deleteTable([
                'TableName' => 'users',
            ]);

            $client->waitUntil('TableNotExists', [
                'TableName' => 'users',
            ]);
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() !== 'ResourceNotFoundException') {
                throw $e;
            }
        }
    }

    protected function getDynamoDbClient(): DynamoDbClient
    {
        $config = [
            'credentials' => $this->app['config']->get('aws.credentials'),
            'endpoint' => $this->app['config']->get('services.dynamodb.endpoint', 'http://localhost:8021'),
            'region' => $this->app['config']->get('aws.region', 'us-east-1'),
            'version' => 'latest',
        ];

        return new DynamoDbClient($config);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('arch.default_items_per_page', 15);
        $app['config']->set('arch.exceptions.should_not_happen', RuntimeException::class);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');

        $endpoint = env('DYNAMODB_ENDPOINT', 'http://localhost:8021');
        $app['config']->set('dynamodb.default', 'default');
        $app['config']->set('dynamodb.connections.default', [
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', 'fakeMyKeyId'),
                'secret' => env('AWS_SECRET_ACCESS_KEY', 'fakeSecretAccessKey'),
            ],
            'endpoint' => $endpoint,
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ]);

        $marshalerOptions = [
            'nullify_invalid' => true,
        ];
        $marshaler = new Marshaler($marshalerOptions);
        $filter = new EmptyAttributeFilter();
        $clientService = new DynamoDbClientService($marshaler, $filter);
        $app->singleton(DynamoDbClientInterface::class, fn (): DynamoDbClientService => $clientService);
        $app->singleton(
            'dynamodb',
            fn (): DynamoDbManager => new DynamoDbManager(
                $app->make(DynamoDbClientInterface::class),
            ),
        );
        DynamoDbModel::setDynamoDbClientService($clientService);
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ArchServiceProvider::class,
        ];
    }

}
