<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Service\DataBuilder;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser
{

    public function __construct(private UserModelService $userModelService, private DataBuilder $baseDataBuilder)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function execute(array $data): User
    {
        // Build normalized data
        $dataNormalized = $this->baseDataBuilder->build($data, [LowercaseEmailPipe::class, UcFirstNamePipe::class]);
        // Store user
        $model = $this->userModelService->create($dataNormalized);

        // Send notification if necessary
        if ($model->email_verified_at === null) {
            // Send a verification link
            Notification::send($model, new VerifyEmail());
        }

        return $model;
    }

}
