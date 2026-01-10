<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\DTO;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\DTO\DataTransferObject;
use Pekral\Arch\Examples\DTO\CreateUserDTO;

test('creates dto with valid data', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    expect($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('John Doe')
        ->and($dto->phone)->toBeNull();
});

test('creates dto with optional phone parameter', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420777888999',
    ]);

    expect($dto->phone)->toBe('+420777888999');
});

test('creates dto with constructor', function (): void {
    $dto = new CreateUserDTO(
        email: 'test@example.com',
        name: 'John Doe',
        phone: '+420777888999',
    );

    expect($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('John Doe')
        ->and($dto->phone)->toBe('+420777888999');
});

test('throws validation exception for invalid email', function (): void {
    CreateUserDTO::validateAndCreate([
        'email' => 'invalid-email',
        'name' => 'John Doe',
    ]);
})->throws(ValidationException::class);

test('throws validation exception for missing email', function (): void {
    CreateUserDTO::validateAndCreate([
        'name' => 'John Doe',
    ]);
})->throws(ValidationException::class);

test('throws validation exception for missing name', function (): void {
    CreateUserDTO::validateAndCreate([
        'email' => 'test@example.com',
    ]);
})->throws(ValidationException::class);

test('throws validation exception for name exceeding max length', function (): void {
    CreateUserDTO::validateAndCreate([
        'email' => 'test@example.com',
        'name' => str_repeat('a', 256),
    ]);
})->throws(ValidationException::class);

test('throws validation exception for invalid phone format', function (): void {
    CreateUserDTO::validateAndCreate([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '123456789',
    ]);
})->throws(ValidationException::class);

test('accepts name at max length boundary', function (): void {
    $dto = CreateUserDTO::validateAndCreate([
        'email' => 'test@example.com',
        'name' => str_repeat('a', 255),
    ]);

    expect($dto->name)->toHaveLength(255);
});

test('accepts valid czech phone number', function (): void {
    $dto = CreateUserDTO::validateAndCreate([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420777888999',
    ]);

    expect($dto->phone)->toBe('+420777888999');
});

test('toArray returns all properties', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420777888999',
    ]);

    $array = $dto->toArray();

    expect($array)->toBe([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420777888999',
    ]);
});

test('toArray includes null values', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    $array = $dto->toArray();

    expect($array)->toBe([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => null,
    ]);
});

test('json_encode works correctly', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    $json = json_encode($dto, JSON_THROW_ON_ERROR);

    expect($json)->toBe('{"email":"test@example.com","name":"John Doe","phone":null}');
});

test('extends DataTransferObject base class', function (): void {
    $dto = CreateUserDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    expect($dto)->toBeInstanceOf(DataTransferObject::class);
});
