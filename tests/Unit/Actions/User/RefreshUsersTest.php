<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\RefreshUsers;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->refreshUsers = app(RefreshUsers::class);
});

test('refresh users refreshes all users', function (): void {
    $users = User::factory()->count(10)->create();
    $refreshedData = $users->map(static fn (User $user): array => [
        'email' => fake()->email(),
        'id' => $user->id,
        'name' => fake()->name(),
        'password' => fake()->password(),
    ]);

    /** @var array<int, array<mixed>> $data */
    $data = $refreshedData->values()->toArray();
    expect($this->refreshUsers->handle($data))->toBe($refreshedData->count());
});

test('import users without data returns zero', function (): void {
    expect($this->refreshUsers->handle([]))->toBe(0);
});
