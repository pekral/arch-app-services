<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Pekral\Arch\ArchServiceProvider;
use RuntimeException;

abstract class TestCase extends Orchestra
{

    use LazilyRefreshDatabase;

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('arch.default_items_per_page', 15);
        $app['config']->set('arch.exceptions.should_not_happen', RuntimeException::class);
        
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'database' => ':memory:',
            'driver' => 'sqlite',
            'foreign_key_constraints' => true,
            'prefix' => '',
        ]);
        
        $app['config']->set('services.dynamodb', [
            'endpoint' => 'http://localhost:8021',
            'key' => 'fakeMyKeyId',
            'region' => 'us-east-1',
            'secret' => 'fakeSecretAccessKey',
        ]);
        
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');
    }

    /**
     * Set up the database.
     */
    protected function setUpDatabase(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        
        $this->artisan('migrate', ['--database' => 'testing']);
        
        $this->runDynamoDbMigrations();
    }

    /**
     * Run DynamoDB migrations.
     */
    protected function runDynamoDbMigrations(): void
    {
        $migrationsPath = __DIR__ . '/database/migrations';
        $migrationFiles = glob($migrationsPath . '/*_*_*_*_*.php');

        foreach ($migrationFiles as $file) {
            if (str_contains(basename($file), 'dynamodb')) {
                $migration = require $file;
                
                if (method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpDatabase();
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ArchServiceProvider::class,
        ];
    }

}
