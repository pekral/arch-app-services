<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ActionLogger;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

final class SendWelcomeEmailWithLogging implements ArchAction
{

    use ActionLogger;

    /**
     * @param array<string, mixed> $context
     */
    public function execute(User $user, array $context = []): void
    {
        $actionName = 'SendWelcomeEmail';
        $logContext = [
            ...$context,
            'user_email' => $user->email,
            'user_id' => $user->id,
        ];

        $this->logActionStart($actionName, $logContext);

        $this->sendEmail();

        $this->logActionSuccess($actionName, [...$logContext, 'email_sent' => true]);
    }

    private function sendEmail(): void
    {
        // Email sending logic would be here
        // For example: Mail::to($user->email)->send(new WelcomeEmail($user));
    }

}
