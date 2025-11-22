<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Tests\Models\User;

final class SendWelcomeEmailWithLogging implements ArchAction
{

    /**
     * @param array<string, mixed> $context
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    public function execute(User $user, array $context = []): void
    {
        $this->sendEmail();
    }

    private function sendEmail(): void
    {
        // Email sending logic would be here
        // For example: Mail::to($user->email)->send(new WelcomeEmail($user));
    }

}
