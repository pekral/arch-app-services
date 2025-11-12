<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\DataBuilder;

use Closure;
use InvalidArgumentException;
use Pekral\Arch\DataBuilder\DataBuilder;

test('build processes data with integer keys', function (): void {
    $builder = new TestClassWithDataBuilder();
    $data = ['name' => 'John', 'email' => 'john@example.com'];
    $pipelines = [
        TestPipe::class,
        TestPipe2::class,
    ];

    $result = $builder->build($data, $pipelines);

    expect($result)->toBe(['name' => 'John', 'email' => 'john@example.com', 'processed' => true, 'processed2' => true]);
});

test('build processes data with string keys', function (): void {
    $builder = new TestClassWithDataBuilder();
    $data = ['name' => 'Jane', 'email' => 'jane@example.com'];
    $pipelines = [
        'pipe1' => TestPipe::class,
        'pipe2' => TestPipe2::class,
    ];

    $result = $builder->build($data, $pipelines);

    expect($result)->toBe(['name' => 'Jane', 'email' => 'jane@example.com', 'processed' => true, 'processed2' => true]);
});

test('build throws exception with mixed keys', function (): void {
    $builder = new TestClassWithDataBuilder();
    $data = ['name' => 'Bob'];
    // phpcs:ignore SlevomatCodingStandard.Arrays.DisallowPartiallyKeyed
    $pipelines = [
        TestPipe::class,
        'specific' => TestPipe2::class,
    ];

    $builder->build($data, $pipelines);
})->throws(InvalidArgumentException::class, 'Pipes keys must be either string or integer');

test('build returns original data with empty pipelines', function (): void {
    $builder = new TestClassWithDataBuilder();
    $data = ['name' => 'Alice'];
    $pipelines = [];

    $result = $builder->build($data, $pipelines);

    expect($result)->toBe(['name' => 'Alice']);
});

final class TestClassWithDataBuilder
{

    use DataBuilder;

}

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
