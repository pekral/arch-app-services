<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User\Pipes;

use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Tests\TestCase;

final class UcFirstNamePipeTest extends TestCase
{

    private UcFirstNamePipe $ucFirstNamePipe;

    public function testHandleWithName(): void
    {
        // Arrange
        $data = ['name' => 'john', 'email' => 'test@example.com'];
        
        // Act
        $result = $this->ucFirstNamePipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testHandleWithUpperCaseName(): void
    {
        // Arrange
        $data = ['name' => 'JOHN', 'email' => 'test@example.com'];
        
        // Act
        $result = $this->ucFirstNamePipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testHandleWithMixedCaseName(): void
    {
        // Arrange
        $data = ['name' => 'jOhN', 'email' => 'test@example.com'];
        
        // Act
        $result = $this->ucFirstNamePipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals('John', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testHandleWithoutName(): void
    {
        // Arrange
        $data = ['email' => 'test@example.com'];
        
        // Act
        $result = $this->ucFirstNamePipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertSame(['email' => 'test@example.com'], $result);
    }

    public function testHandleWithNonStringName(): void
    {
        // Arrange
        $data = ['name' => 123, 'email' => 'test@example.com'];
        
        // Act
        $result = $this->ucFirstNamePipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals(123, $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ucFirstNamePipe = new UcFirstNamePipe();
    }

}
