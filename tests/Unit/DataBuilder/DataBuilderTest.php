<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\DataBuilder;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Tests\TestCase;

final class DataBuilderTest extends TestCase
{

    private DataBuilder $dataBuilder;

    public function testBuildWithSinglePipe(): void
    {
        // Arrange
        $data = ['email' => 'TEST@EXAMPLE.COM', 'name' => 'john'];
        $pipes = [LowercaseEmailPipe::class];
        
        // Act
        $result = $this->dataBuilder->build($data, $pipes);
        
        // Assert
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('john', $result['name']);
    }

    public function testBuildWithMultiplePipes(): void
    {
        // Arrange
        $data = ['email' => 'TEST@EXAMPLE.COM', 'name' => 'john'];
        $pipes = [LowercaseEmailPipe::class, UcFirstNamePipe::class];
        
        // Act
        $result = $this->dataBuilder->build($data, $pipes);
        
        // Assert
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('John', $result['name']);
    }

    public function testBuildWithEmptyPipes(): void
    {
        // Arrange
        $data = ['email' => 'TEST@EXAMPLE.COM', 'name' => 'john'];
        $pipes = [];
        
        // Act
        $result = $this->dataBuilder->build($data, $pipes);
        
        // Assert
        $this->assertSame($data, $result);
    }

    public function testBuildWithEmptyData(): void
    {
        // Arrange
        $data = [];
        $pipes = [LowercaseEmailPipe::class, UcFirstNamePipe::class];
        
        // Act
        $result = $this->dataBuilder->build($data, $pipes);
        
        // Assert
        $this->assertSame([], $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataBuilder = app(DataBuilder::class);
    }

}
