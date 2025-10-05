<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\VerifyUserAction;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;

final class VerifyUserActionTest extends TestCase
{

    private VerifyUserAction $verifyUserAction;

    public function testVerification(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $this->verifyUserAction->handle($user);

        // Assert
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testVerificationHasBeenSent(): void
    {
        // Arrange
        $user = User::factory()->create(['email_verified_at' => now()]);
        // Act
        $this->verifyUserAction->handle($user);
        // Assert
        Notification::assertNothingSent();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->verifyUserAction = app(VerifyUserAction::class);
    }

}
