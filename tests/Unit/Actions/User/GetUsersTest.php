<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\GetUsers;
use Pekral\Arch\Tests\Models\User;

test('get users returns paginated users', function (): void {
    $getUsers = app(GetUsers::class);
    $users = User::factory()->count(30)->create();
    $usersIds = $users->pluck('id')->toArray();

    $foundUsers = $getUsers->handle();

    expect($foundUsers)->toHaveCount(config()->integer('arch.default_items_per_page'));
    
    $foundUsers->collect()->each(function (User $user) use ($usersIds): void {
        expect(in_array($user->id, $usersIds, true))->toBeTrue();
    });
});

test('get users with filters returns filtered users', function (): void {
    $getUsers = app(GetUsers::class);
    User::factory()->count(5)->create(['name' => 'John']);
    User::factory()->count(5)->create(['name' => 'Jane']);
    
    $foundUsers = $getUsers->handle(['name' => 'John']);
    
    expect($foundUsers)->toHaveCount(5);
    
    $foundUsers->collect()->each(function (User $user): void {
        expect($user->name)->toBe('John');
    });
});
