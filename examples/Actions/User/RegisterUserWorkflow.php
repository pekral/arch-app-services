<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Result\Result;
use Pekral\Arch\Tests\Models\User;

/**
 * Demonstrates Result pattern composition for complex workflows.
 *
 * This action chains multiple operations:
 * 1. Create user
 * 2. Send welcome email
 * 3. Subscribe to newsletter
 *
 * If any step fails, the entire workflow returns the error.
 */
final readonly class RegisterUserWorkflow implements ArchAction
{

    public function __construct(private CreateUserWithResult $createUserWithResult, private SendWelcomeEmailWithLogging $sendWelcomeEmailWithLogging)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<\Pekral\Arch\Tests\Models\User, \Pekral\Arch\Examples\Actions\User\Errors\UserFailure>
     */
    public function execute(array $data): Result
    {
        return $this->createUserWithResult->execute($data)
            ->onSuccess(function (User $user): void {
                $this->sendWelcomeEmailWithLogging->execute($user);
            })
            ->onSuccess(function (User $user): void {
                $this->logRegistration($user);
            });
    }

    /**
     * @param array<string, mixed> $data
     * @return \Pekral\Arch\Result\Result<array{user: \Pekral\Arch\Tests\Models\User, emailSent: bool}, \Pekral\Arch\Examples\Actions\User\Errors\UserFailure>
     */
    public function executeWithDetails(array $data): Result
    {
        return $this->createUserWithResult->execute($data)
            ->map(function (User $user): array {
                $this->sendWelcomeEmailWithLogging->execute($user);
                $this->logRegistration($user);

                return [
                    'emailSent' => true,
                    'user' => $user,
                ];
            });
    }

    private function logRegistration(User $user): void
    {
        logger()->info('User registered successfully', [
            'email' => $user->email,
            'user_id' => $user->id,
        ]);
    }

}
