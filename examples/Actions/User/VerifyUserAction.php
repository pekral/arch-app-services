<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Tests\Models\User;

final class VerifyUserAction
{

    public function handle(User $user): void
    {
        if ($user->email_verified_at === null) {
            Notification::send($user, new VerifyEmail());
        }
    }

}
