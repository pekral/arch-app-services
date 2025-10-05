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
        
        // Set up test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'database' => ':memory:',
            'driver' => 'sqlite',
            'foreign_key_constraints' => true,
            'prefix' => '',
        ]);
        
        // Set test environment configuration
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
        // Test database is always :memory: SQLite
        
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setUpDatabase();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            ArchServiceProvider::class,
        ];
    }

}
