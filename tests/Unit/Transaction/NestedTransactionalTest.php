<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use InvalidArgumentException;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\NestedTransactional;
use Pekral\Arch\Transaction\Transactional;
use RuntimeException;

test('savepoint executes callback and returns result', function (): void {
    $action = new class () {

        use NestedTransactional;
        use Transactional;

        public function run(): User
        {
            return $this->transaction(
                fn (): User => $this->savepoint('sp_create', fn (): User => User::factory()->create(['name' => 'Savepoint User'])),
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

        use NestedTransactional;
        use Transactional;

        public function run(): User
        {
            return $this->transaction(function (): User {
                $primary = $this->savepoint('sp_primary', fn (): User => User::factory()->create(['name' => 'Primary User']));

                try {
                    $this->savepoint('sp_secondary', function (): never {
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

        use NestedTransactional;
        use Transactional;

        public function run(): void
        {
            $this->transaction(function (): void {
                $this->savepoint('sp_fail', function (): never {
                    throw new RuntimeException('Savepoint failure');
                });
            });
        }
    
    };

    expect(fn () => $action->run())->toThrow(RuntimeException::class, 'Savepoint failure');
});

test('savepoint with explicit connection executes callback', function (): void {
    $action = new class () {

        use NestedTransactional;
        use Transactional;

        public function run(): User
        {
            return $this->transaction(fn (): User => $this->savepoint(
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
    $action = new class () {

        use NestedTransactional;
        use Transactional;

        public function run(): void
        {
            $this->transaction(fn (): mixed => $this->savepoint('invalid; DROP TABLE users', fn (): string => 'should not reach'));
        }

    };

    expect(fn () => $action->run())->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});

test('savepoint rejects name starting with number', function (): void {
    $action = new class () {

        use NestedTransactional;
        use Transactional;

        public function run(): void
        {
            $this->transaction(fn (): mixed => $this->savepoint('1invalid', fn (): string => 'should not reach'));
        }

    };

    expect(fn () => $action->run())->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});

test('savepoint rejects empty name', function (): void {
    $action = new class () {

        use NestedTransactional;
        use Transactional;

        public function run(): void
        {
            $this->transaction(fn (): mixed => $this->savepoint('', fn (): string => 'should not reach'));
        }

    };

    expect(fn () => $action->run())->toThrow(InvalidArgumentException::class, 'contains invalid characters');
});
