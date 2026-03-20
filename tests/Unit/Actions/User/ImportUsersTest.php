<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\ImportUsers;

test('import users imports all users', function (): void {
    $importUsers = app(ImportUsers::class);
    $data = [
        [
            'email' => fake()->email(),
            'name' => fake()->name(),
            'password' => fake()->password(),
        ],
        [
            'email' => fake()->email(),
            'name' => fake()->name(),
            'password' => fake()->password(),
        ],
    ];
    
    expect(($importUsers)($data))->toBe(count($data));
});

test('import users without data returns zero', function (): void {
    $importUsers = app(ImportUsers::class);
    
    expect(($importUsers)([]))->toBe(0);
});
