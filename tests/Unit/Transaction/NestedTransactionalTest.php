<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use InvalidArgumentException;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\NestedTransactionalInvoker;
use Pekral\Arch\Transaction\Transactional;
use RuntimeException;

test('savepoint executes callback and returns result', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): User
        {
            $invoker = new NestedTransactionalInvoker();

            return $this->transaction(
                fn (): User => $invoker->runSavepoint('sp_create', fn (): User => User::factory()->create(['name' => 'Savepoint User'])),
            );
        }
    
    };

    $user = $action->run();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Savepoint User')
        ->and(User::query()->where('name', 'Savepoint User')->exists())->toBeTrue();
});

test('savepoint rolls back to savepoint on exception but preserves outer transaction', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): User
        {
            $invoker = new NestedTransactionalInvoker();

            return $this->transaction(function () use ($invoker): User {
                $primary = $invoker->runSavepoint('sp_primary', fn (): User => User::factory()->create(['name' => 'Primary User']));

                try {
                    $invoker->runSavepoint('sp_secondary', function (): never {
                        User::factory()->create(['name' => 'Secondary User']);

                        throw new RuntimeException('Secondary failed');
                    });
                } catch (RuntimeException) {
                    // Secondary savepoint rolled back; primary preserved
                }

                return $primary;
            });
        }
    
    };

    $primary = $action->run();

    expect($primary)->toBeInstanceOf(User::class)
        ->and(User::query()->where('name', 'Primary User')->exists())->toBeTrue()
        ->and(User::query()->where('name', 'Secondary User')->exists())->toBeFalse();
});

test('savepoint rethrows exception after rollback', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): void
        {
            $invoker = new NestedTransactionalInvoker();

            $this->transaction(function () use ($invoker): void {
                $invoker->runSavepoint('sp_fail', function (): never {
                    throw new RuntimeException('Savepoint failure');
                });
            });
        }
    
    };

    expect(fn () => $action->run())->toThrow(RuntimeException::class, 'Savepoint failure');
});

test('savepoint with explicit connection executes callback', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): User
        {
            $invoker = new NestedTransactionalInvoker();

            return $this->transaction(fn (): User => $invoker->runSavepoint(
                'sp_conn',
                fn (): User => User::factory()->create(['name' => 'Connection Savepoint']),
                'testing',
            ));
        }
    
    };

    $user = $action->run();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Connection Savepoint');
});

test('savepoint rejects invalid name with special characters', function (): void {
    $invoker = new NestedTransactionalInvoker();

    expect(fn (): mixed => $invoker->runSavepoint('invalid; DROP TABLE users', fn (): string => 'should not reach'))
        ->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});

test('savepoint rejects name starting with number', function (): void {
    $invoker = new NestedTransactionalInvoker();

    expect(fn (): mixed => $invoker->runSavepoint('1invalid', fn (): string => 'should not reach'))
        ->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});

test('savepoint rejects empty name', function (): void {
    $invoker = new NestedTransactionalInvoker();

    expect(fn (): mixed => $invoker->runSavepoint('', fn (): string => 'should not reach'))
        ->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});
