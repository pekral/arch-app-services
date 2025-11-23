<?php

declare(strict_types = 1);

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class () extends Migration {

    public function up(): void
    {
        $this->loadTableDefinitions()->each(function (array $tableConfig): void {
            $this->createDynamoDbTable($tableConfig);
        });
    }

    protected function loadTableDefinitions(): Collection
    {
        $tablesPath = __DIR__ . '/../dynamodb-tables';

        if (!is_dir($tablesPath)) {
            return collect();
        }

        $jsonFiles = glob($tablesPath . '/*.json');

        if ($jsonFiles === false) {
            return collect();
        }

        return collect($jsonFiles)
            ->map(function (string $jsonFile): ?array {
                $content = file_get_contents($jsonFile);

                if ($content === false) {
                    return null;
                }

                $tableConfig = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($tableConfig) && isset($tableConfig['TableName'])) {
                    return $tableConfig;
                }

                return null;
            })
            ->filter()
            ->values();
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function createDynamoDbTable(array $config): void
    {
        $client = $this->getDynamoDbClient();
        
        if ($this->tableExists($client, $config['TableName'])) {
            $client->deleteTable(['TableName' => $config['TableName']]);
            $client->waitUntil('TableNotExists', ['TableName' => $config['TableName']]);
        }
        
        $client->createTable($config);
        $client->waitUntil('TableExists', ['TableName' => $config['TableName']]);
    }

    protected function tableExists(DynamoDbClient $client, string $tableName): bool
    {
        try {
            $client->describeTable(['TableName' => $tableName]);

            return true;
        } catch (DynamoDbException $e) {
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                return false;
            }

            throw $e;
        }
    }

    private function getDynamoDbClient(): DynamoDbClient
    {
        /** @var array{credentials: array{key: string, secret: string}, endpoint: string, region: string, version: string} $config */
        $config = [
            'credentials' => [
                'key' => config('services.dynamodb.key', 'fakeMyKeyId'),
                'secret' => config('services.dynamodb.secret', 'fakeSecretAccessKey'),
            ],
            'endpoint' => config('services.dynamodb.endpoint', 'http://localhost:8021'),
            'region' => config('services.dynamodb.region', 'us-east-1'),
            'version' => 'latest',
        ];

        return new DynamoDbClient($config);
    }

};
