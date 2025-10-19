<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\DataBuilder;

use Closure;
use InvalidArgumentException;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\Tests\TestCase;

final class DataBuilderTest extends TestCase
{

    private TestClassWithDataBuilder $testClassWithDataBuilder;

    public function testBuildWithIntegerKeys(): void
    {
        // Arrange
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $pipelines = [
            TestPipe::class,
            TestPipe2::class,
        ];

        // Act
        $result = $this->testClassWithDataBuilder->build($data, $pipelines);

        // Assert
        $this->assertEquals(['name' => 'John', 'email' => 'john@example.com', 'processed' => true, 'processed2' => true], $result);
    }

    public function testBuildWithStringKeys(): void
    {
        // Arrange
        $data = ['name' => 'Jane', 'email' => 'jane@example.com'];
        $pipelines = [
            'pipe1' => TestPipe::class,
            'pipe2' => TestPipe2::class,
        ];

        // Act
        $result = $this->testClassWithDataBuilder->build($data, $pipelines);

        // Assert
        $this->assertEquals(['name' => 'Jane', 'email' => 'jane@example.com', 'processed' => true, 'processed2' => true], $result);
    }

    public function testBuildWithMixedKeys(): void
    {
        // Arrange
        $data = ['name' => 'Bob'];
        // phpcs:ignore SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed
        $pipelines = [
            TestPipe::class,
            'specific' => TestPipe2::class,
        ];

        // Act & Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pipes keys must be either string or integer');
        
        $this->testClassWithDataBuilder->build($data, $pipelines);
    }

    public function testBuildWithEmptyPipelines(): void
    {
        // Arrange
        $data = ['name' => 'Alice'];
        $pipelines = [];

        // Act
        $result = $this->testClassWithDataBuilder->build($data, $pipelines);

        // Assert
        $this->assertSame(['name' => 'Alice'], $result);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testClassWithDataBuilder = new TestClassWithDataBuilder();
    }

}

/**
 * Test class for DataBuilder trait
 */
final class TestClassWithDataBuilder
{

    use DataBuilder;

}

/**
 * Test pipe that adds a 'processed' flag
 */
final class TestPipe
{

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data, Closure $next): array
    {
        $data['processed'] = true;
        
        /** @var array<string, mixed> $result */
        $result = $next($data);
        
        return $result;
    }

}

/**
 * Test pipe that adds a 'processed2' flag
 */
final class TestPipe2
{

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function handle(array $data, Closure $next): array
    {
        $data['processed2'] = true;
        
        /** @var array<string, mixed> $result */
        $result = $next($data);
        
        return $result;
    }

}
