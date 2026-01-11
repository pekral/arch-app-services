<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\DTO;

use Illuminate\Validation\ValidationException;
use Pekral\Arch\DTO\DataTransferObject;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;

test('creates dto with valid data', function (): void {
    $dto = TestDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    expect($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('John Doe')
        ->and($dto->phone)->toBeNull();
});

test('creates dto with optional parameter', function (): void {
    $dto = TestDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420123456789',
    ]);

    expect($dto->phone)->toBe('+420123456789');
});

test('creates dto with constructor', function (): void {
    $dto = new TestDTO(
        email: 'test@example.com',
        name: 'John Doe',
        phone: '+420123456789',
    );

    expect($dto->email)->toBe('test@example.com')
        ->and($dto->name)->toBe('John Doe')
        ->and($dto->phone)->toBe('+420123456789');
});

test('throws validation exception for invalid email', function (): void {
    TestDTO::validateAndCreate([
        'email' => 'invalid-email',
        'name' => 'John Doe',
    ]);
})->throws(ValidationException::class);

test('throws validation exception for missing required field', function (): void {
    TestDTO::validateAndCreate([
        'email' => 'test@example.com',
        'name' => '',
    ]);
})->throws(ValidationException::class);

test('toArray returns all properties', function (): void {
    $dto = TestDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420123456789',
    ]);

    $array = $dto->toArray();

    expect($array)->toBe([
        'email' => 'test@example.com',
        'name' => 'John Doe',
        'phone' => '+420123456789',
    ]);
});

test('toArray includes null values', function (): void {
    $dto = TestDTO::from([
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
    $dto = TestDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    $json = json_encode($dto, JSON_THROW_ON_ERROR);

    expect($json)->toBe('{"email":"test@example.com","name":"John Doe","phone":null}');
});

test('extends DataTransferObject base class', function (): void {
    $dto = TestDTO::from([
        'email' => 'test@example.com',
        'name' => 'John Doe',
    ]);

    expect($dto)->toBeInstanceOf(DataTransferObject::class);
});

final class TestDTO extends DataTransferObject
{

    public function __construct(
        #[ Email, Required]
        public string $email,
        #[ Min(1), Required]
        public string $name,
        #[Max(20)]
        public ?string $phone = null,
    ) {
    }

}
