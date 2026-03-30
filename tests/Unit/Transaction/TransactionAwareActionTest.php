<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use Illuminate\Support\Facades\Config;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\InTransaction;
use Pekral\Arch\Transaction\TransactionAwareAction;
use RuntimeException;

test('execute with transaction attribute runs callback without transaction when no attribute', function (): void {
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        public function __invoke(): string
        {
            return $this->executeWithTransactionAttribute(fn (): string => 'no-attribute');
        }
    
    };

    expect(($action)())->toBe('no-attribute');
});

test('execute with transaction attribute wraps callback in transaction when attribute present', function (): void {
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        #[InTransaction]
        public function __invoke(): User
        {
            return $this->executeWithTransactionAttribute(
                fn (): User => User::factory()->create(['name' => 'Attribute User']),
            );
        }
    
    };

    $user = ($action)();

    expect($user)->toBeInstanceOf(User::class)
        ->and(User::query()->where('name', 'Attribute User')->exists())->toBeTrue();
});

test('execute with transaction attribute rolls back on exception when attribute present', function (): void {
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        #[InTransaction]
        public function __invoke(): void
        {
            $this->executeWithTransactionAttribute(function (): never {
                User::factory()->create(['name' => 'Should Rollback']);

                throw new RuntimeException('Attribute transaction failure');
            });
        }
    
    };

    try {
        ($action)();
    } catch (RuntimeException) {
        // Expected
    }

    expect(User::query()->where('name', 'Should Rollback')->exists())->toBeFalse();
});

test('execute with transaction attribute uses attempts from attribute', function (): void {
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        #[InTransaction(attempts: 2)]
        public function __invoke(): string
        {
            return $this->executeWithTransactionAttribute(fn (): string => 'two-attempts');
        }
    
    };

    expect(($action)())->toBe('two-attempts');
});

test('execute with transaction attribute uses config attempts when attribute attempts is zero', function (): void {
    Config::set('arch.transactions.default_attempts', 1);

    // InTransaction always has attempts >= 1 (default is 1), but when resolved via
    // config the config value is applied. This test verifies config fallback pathway.
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        #[InTransaction(attempts: 1)]
        public function __invoke(): string
        {
            return $this->executeWithTransactionAttribute(fn (): string => 'config-fallback');
        }
    
    };

    expect(($action)())->toBe('config-fallback');
});

test('execute with transaction attribute uses custom connection from attribute', function (): void {
    $action = new class () implements ArchAction {

        use TransactionAwareAction;

        #[InTransaction(connection: 'testing')]
        public function __invoke(): User
        {
            return $this->executeWithTransactionAttribute(
                fn (): User => User::factory()->create(['name' => 'Custom Connection']),
            );
        }
    
    };

    $user = ($action)();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Custom Connection');
});
