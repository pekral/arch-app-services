<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;

test('handle with name converts to ucfirst', function (): void {
    $ucFirstNamePipe = new UcFirstNamePipe();
    $data = ['name' => 'john', 'email' => 'test@example.com'];
    
    $result = $ucFirstNamePipe->handle($data, static fn ($data): array => $data);
    
    expect($result['name'])->toBe('John')
        ->and($result['email'])->toBe('test@example.com');
});

test('handle with upper case name converts to ucfirst', function (): void {
    $ucFirstNamePipe = new UcFirstNamePipe();
    $data = ['name' => 'JOHN', 'email' => 'test@example.com'];
    
    $result = $ucFirstNamePipe->handle($data, static fn ($data): array => $data);
    
    expect($result['name'])->toBe('John')
        ->and($result['email'])->toBe('test@example.com');
});

test('handle with mixed case name converts to ucfirst', function (): void {
    $ucFirstNamePipe = new UcFirstNamePipe();
    $data = ['name' => 'jOhN', 'email' => 'test@example.com'];
    
    $result = $ucFirstNamePipe->handle($data, static fn ($data): array => $data);
    
    expect($result['name'])->toBe('John')
        ->and($result['email'])->toBe('test@example.com');
});

test('handle without name returns unchanged data', function (): void {
    $ucFirstNamePipe = new UcFirstNamePipe();
    $data = ['email' => 'test@example.com'];
    
    $result = $ucFirstNamePipe->handle($data, static fn ($data): array => $data);
    
    expect($result)->toBe(['email' => 'test@example.com']);
});

test('handle with non string name returns unchanged', function (): void {
    $ucFirstNamePipe = new UcFirstNamePipe();
    $data = ['name' => 123, 'email' => 'test@example.com'];
    
    $result = $ucFirstNamePipe->handle($data, static fn ($data): array => $data);
    
    expect($result['name'])->toBe(123)
        ->and($result['email'])->toBe('test@example.com');
});
