<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\CountVerifiedUsers;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    $this->countVerifiedUsers = app(CountVerifiedUsers::class);
});

test('count verified users returns correct count', function (): void {
    User::factory()->count(10)->create(['email_verified_at' => null]);
    $verifiedUsers = User::factory()->count(10)->create(['email_verified_at' => now()]);
    
    expect($this->countVerifiedUsers->handle())->toBe($verifiedUsers->count());
});
