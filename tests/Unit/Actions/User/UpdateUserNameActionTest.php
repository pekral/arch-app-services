<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\UpdateUserName;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->updateUserName = app(UpdateUserName::class);
});

test('update user name updates correctly', function (): void {
    $user = User::factory()->create(['name' => 'john']);
    $newName = 'John';
    
    $this->updateUserName->handle($newName, $user);
    
    $user->fresh();
    expect($user->name)->toBe($newName);
});
