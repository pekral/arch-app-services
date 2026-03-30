<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use Illuminate\Support\Facades\Config;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\TransactionAwareActionWithAttributeInvoker;
use Pekral\Arch\Transaction\TransactionAwareActionWithoutAttributeInvoker;
use RuntimeException;

test('execute with transaction attribute runs callback without transaction when no attribute', function (): void {
    $invoker = new TransactionAwareActionWithoutAttributeInvoker();

    expect($invoker(fn (): string => 'no-attribute'))->toBe('no-attribute');
});

test('execute with transaction attribute wraps callback in transaction when attribute present', function (): void {
    $invoker = new TransactionAwareActionWithAttributeInvoker();

    $user = $invoker(fn (): User => User::factory()->create(['name' => 'Attribute User']));

    expect($user)->toBeInstanceOf(User::class)
        ->and(User::query()->where('name', 'Attribute User')->exists())->toBeTrue();
});

test('execute with transaction attribute rolls back on exception when attribute present', function (): void {
    try {
        $invoker = new TransactionAwareActionWithAttributeInvoker();
        $invoker(function (): never {
            User::factory()->create(['name' => 'Should Rollback']);

            throw new RuntimeException('Attribute transaction failure');
        });
    } catch (RuntimeException) {
        // Expected
    }

    expect(User::query()->where('name', 'Should Rollback')->exists())->toBeFalse();
});

test('execute with transaction attribute uses attempts from attribute', function (): void {
    Config::set('arch.transactions.default_attempts', 2);

    $invoker = new TransactionAwareActionWithAttributeInvoker();

    expect($invoker(fn (): string => 'two-attempts'))->toBe('two-attempts');
});

test('execute with transaction attribute uses config attempts when attribute attempts is zero', function (): void {
    Config::set('arch.transactions.default_attempts', 'nope');

    $invoker = new TransactionAwareActionWithAttributeInvoker();

    expect($invoker(fn (): string => 'config-fallback'))->toBe('config-fallback');
});

test('execute with transaction attribute uses custom connection from attribute', function (): void {
    Config::set('arch.transactions.default_attempts', 1);

    $invoker = new TransactionAwareActionWithAttributeInvoker();

    $user = $invoker(
        fn (): User => User::factory()->create(['name' => 'Custom Connection']),
    );

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Custom Connection');
});
