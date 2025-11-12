<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\UpdateUserName;
use Pekral\Arch\Tests\Models\User;

test('update user name updates correctly', function (): void {
    $updateUserName = app(UpdateUserName::class);
    $user = User::factory()->create(['name' => 'john']);
    $newName = 'John';
    
    $updateUserName->handle($newName, $user);
    
    $user->fresh();
    expect($user->name)->toBe($newName);
});
