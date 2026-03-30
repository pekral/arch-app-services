<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use Attribute;
use Pekral\Arch\Transaction\InTransaction;
use ReflectionClass;

test('in transaction attribute has default attempts of one', function (): void {
    $attribute = new InTransaction();

    expect($attribute->attempts)->toBe(1);
});

test('in transaction attribute accepts custom attempts', function (): void {
    $attribute = new InTransaction(attempts: 3);

    expect($attribute->attempts)->toBe(3);
});

test('in transaction attribute has null connection by default', function (): void {
    $attribute = new InTransaction();

    expect($attribute->connection)->toBeNull();
});

test('in transaction attribute accepts custom connection', function (): void {
    $attribute = new InTransaction(connection: 'mysql');

    expect($attribute->connection)->toBe('mysql');
});

test('in transaction attribute accepts both attempts and connection', function (): void {
    $attribute = new InTransaction(attempts: 5, connection: 'pgsql');

    expect($attribute->attempts)->toBe(5)
        ->and($attribute->connection)->toBe('pgsql');
});

test('in transaction is a valid php attribute targeting methods', function (): void {
    $reflection = new ReflectionClass(InTransaction::class);
    $attributes = $reflection->getAttributes(Attribute::class);

    expect($attributes)->not->toBeEmpty();

    $attributeInstance = $attributes[0]->newInstance();

    expect($attributeInstance->flags)->toBe(Attribute::TARGET_METHOD);
});
