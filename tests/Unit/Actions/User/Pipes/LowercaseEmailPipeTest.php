<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;

beforeEach(function (): void {
    $this->lowercaseEmailPipe = new LowercaseEmailPipe();
});

test('handle with email converts to lowercase', function (): void {
    $data = ['email' => 'TEST@EXAMPLE.COM', 'name' => 'John'];
    
    $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
    
    expect($result['email'])->toBe('test@example.com')
        ->and($result['name'])->toBe('John');
});

test('handle without email returns unchanged data', function (): void {
    $data = ['name' => 'John'];
    
    $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
    
    expect($result)->toBe(['name' => 'John']);
});

test('handle with non string email returns unchanged', function (): void {
    $data = ['email' => 123, 'name' => 'John'];
    
    $result = $this->lowercaseEmailPipe->handle($data, static fn ($data): array => $data);
    
    expect($result['email'])->toBe(123)
        ->and($result['name'])->toBe('John');
});
