<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\ImportUsers;

beforeEach(function (): void {
    $this->importUsers = app(ImportUsers::class);
});

test('import users imports all users', function (): void {
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
    
    expect($this->importUsers->handle($data))->toBe(count($data));
});

test('import users without data returns zero', function (): void {
    expect($this->importUsers->handle([]))->toBe(0);
});
