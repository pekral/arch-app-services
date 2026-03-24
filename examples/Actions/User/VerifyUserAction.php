<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Tests\Models\User;

/**
 * Sends an email verification notification to a user who has not yet verified their address.
 * This is an internal helper invoked by entry-point actions — not an ArchAction entry point itself.
 */
final readonly class VerifyUserAction
{

    public function __invoke(User $user): void
    {
        if ($user->email_verified_at === null) {
            Notification::send($user, new VerifyEmail());
        }
    }

}
