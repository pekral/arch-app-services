<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit;

use Pekral\Arch\ArchServiceProvider;
use Pekral\Arch\Tests\TestCase;

final class ArchServiceProviderTest extends TestCase
{

    public function testServiceProviderIsRegistered(): void
    {
        $app = $this->app;
        \assert($app instanceof \Illuminate\Foundation\Application);
        $this->assertTrue($app->providerIsLoaded(ArchServiceProvider::class));
    }

    public function testConfigIsMerged(): void
    {
        $app = $this->app;
        \assert($app instanceof \Illuminate\Foundation\Application);
        /** @var array<string, mixed> $config */
        $config = $app['config'];
        $this->assertArrayHasKey('arch', $config);
        /** @var array<string, mixed> $archConfig */
        $archConfig = $config['arch'];
        $this->assertArrayHasKey('default_items_per_page', $archConfig);
    }

    public function testConfigCanBePublished(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'arch-config']);
        
        $this->assertFileExists(config_path('arch.php'));
    }

}
