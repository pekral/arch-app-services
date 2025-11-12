<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Exceptions;

use Pekral\Arch\Exceptions\MassUpdateNotAvailable;
use Pekral\Arch\Tests\TestCase;

final class MassUpdateNotAvailableExceptionTest extends TestCase
{

    public function testMissingPackage(): void
    {
        // Act
        $exception = MassUpdateNotAvailable::missingPackage();

        // Assert
        $this->assertInstanceOf(MassUpdateNotAvailable::class, $exception);
        $this->assertStringContainsString('iksaku/laravel-mass-update', $exception->getMessage());
        $this->assertStringContainsString('composer require', $exception->getMessage());
    }

    public function testTraitNotUsed(): void
    {
        // Arrange
        $modelClass = 'App\Models\User';

        // Act
        $exception = MassUpdateNotAvailable::traitNotUsed($modelClass);

        // Assert
        $this->assertInstanceOf(MassUpdateNotAvailable::class, $exception);
        $this->assertStringContainsString($modelClass, $exception->getMessage());
        $this->assertStringContainsString('MassUpdatable', $exception->getMessage());
        $this->assertStringContainsString('use Iksaku\Laravel\MassUpdate\MassUpdatable', $exception->getMessage());
    }

}
