<?php

declare(strict_types = 1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\VerifyUserAction;
use Pekral\Arch\Tests\Models\User;

test('verification sends notification', function (): void {
    Notification::fake();
    $verifyUserAction = app(VerifyUserAction::class);
    $user = User::factory()->create();

    $verifyUserAction->handle($user);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('verification has been sent skips notification', function (): void {
    Notification::fake();
    $verifyUserAction = app(VerifyUserAction::class);
    $user = User::factory()->create(['email_verified_at' => now()]);
    
    $verifyUserAction->handle($user);
    
    Notification::assertNothingSent();
});
