<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Transaction;

use Illuminate\Support\Facades\Config;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Transaction\Transactional;
use RuntimeException;

test('transaction executes callback and returns result', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): User
        {
            return $this->transaction(fn (): User => User::factory()->create(['name' => 'Alice']));
        }
    
    };

    $user = $action->run();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Alice')
        ->and(User::query()->where('name', 'Alice')->exists())->toBeTrue();
});

test('transaction rolls back on exception', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): void
        {
            $this->transaction(function (): never {
                User::factory()->create(['name' => 'Rollback User']);

                throw new RuntimeException('Intentional failure');
            });
        }
    
    };

    try {
        $action->run();
    } catch (RuntimeException) {
        // Expected
    }

    expect(User::query()->where('name', 'Rollback User')->exists())->toBeFalse();
});

test('transaction uses config default attempts when null is passed', function (): void {
    Config::set('arch.transactions.default_attempts', 1);

    $action = new class () {

        use Transactional;

        public function run(): string
        {
            return $this->transaction(fn (): string => 'ok');
        }
    
    };

    expect($action->run())->toBe('ok');
});

test('transaction uses explicit attempts when provided', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): string
        {
            return $this->transaction(fn (): string => 'done', 2);
        }
    
    };

    expect($action->run())->toBe('done');
});

test('transaction with explicit connection executes callback', function (): void {
    $action = new class () {

        use Transactional;

        public function run(): User
        {
            return $this->transaction(
                fn (): User => User::factory()->create(['name' => 'Connection User']),
                null,
                'testing',
            );
        }
    
    };

    $user = $action->run();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Connection User');
});

test('transaction with null attempts falls back to config value', function (): void {
    Config::set('arch.transactions.default_attempts', 2);

    $action = new class () {

        use Transactional;

        public function run(): string
        {
            return $this->transaction(fn (): string => 'config-attempts');
        }
    
    };

    expect($action->run())->toBe('config-attempts');
});
