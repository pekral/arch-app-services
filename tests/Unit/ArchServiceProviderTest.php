<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit;

use Pekral\Arch\ArchServiceProvider;
use Pekral\Arch\Tests\TestCase;

final class ArchServiceProviderTest extends TestCase
{

    public function testRegister(): void
    {
        // Arrange
        $provider = new ArchServiceProvider(app());
        
        // Act
        $provider->register();
        
        // Assert
        $this->assertTrue(config()->has('arch'));
        $this->assertEquals(15, config('arch.default_items_per_page'));
    }

    public function testBootInConsole(): void
    {
        // Arrange
        $provider = new ArchServiceProvider(app());
        
        // Act
        $provider->boot();
        
        // Assert
        $this->assertTrue(config()->has('arch'));
    }

}
