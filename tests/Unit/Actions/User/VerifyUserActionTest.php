<?php

declare(strict_types = 1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\VerifyUserAction;
use Pekral\Arch\Tests\Models\User;

beforeEach(function (): void {
    Notification::fake();
    $this->verifyUserAction = app(VerifyUserAction::class);
});

test('verification sends notification', function (): void {
    $user = User::factory()->create();

    $this->verifyUserAction->handle($user);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification has been sent skips notification', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);
    
    $this->verifyUserAction->handle($user);
    
    Notification::assertNothingSent();
});
