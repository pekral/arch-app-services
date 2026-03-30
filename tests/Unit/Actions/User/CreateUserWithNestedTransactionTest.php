<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\CreateUserWithNestedTransaction;
use Pekral\Arch\Tests\Models\User;

test('create user with nested transaction creates both users when both succeed', function (): void {
    $action = app(CreateUserWithNestedTransaction::class);
    $primaryData = ['name' => 'Primary Nested', 'email' => 'primary-nested@example.com', 'password' => 'secret'];
    $secondaryData = ['name' => 'Secondary Nested', 'email' => 'secondary-nested@example.com', 'password' => 'secret'];

    $result = ($action)($primaryData, $secondaryData);

    expect($result->primary)->toBeInstanceOf(User::class)
        ->and($result->secondary)->toBeInstanceOf(User::class)
        ->and(User::query()->where('email', 'primary-nested@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'secondary-nested@example.com')->exists())->toBeTrue();
});
