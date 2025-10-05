<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User\Pipes;

use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Tests\TestCase;

final class LowercaseEmailPipeTest extends TestCase
{

    private LowercaseEmailPipe $lowercaseEmailPipe;

    public function testHandleWithEmail(): void
    {
        // Arrange
        $data = ['email' => 'TEST@EXAMPLE.COM', 'name' => 'John'];
        
        // Act
        $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('John', $result['name']);
    }

    public function testHandleWithoutEmail(): void
    {
        // Arrange
        $data = ['name' => 'John'];
        
        // Act
        $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertSame(['name' => 'John'], $result);
    }

    public function testHandleWithNonStringEmail(): void
    {
        // Arrange
        $data = ['email' => 123, 'name' => 'John'];
        
        // Act
        $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
        
        // Assert
        $this->assertEquals(123, $result['email']);
        $this->assertEquals('John', $result['name']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->lowercaseEmailPipe = new LowercaseEmailPipe();
    }

}
