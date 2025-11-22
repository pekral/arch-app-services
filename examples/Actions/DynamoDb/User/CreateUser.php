<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\DynamoDb\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Models\DynamoDb\User;
use Pekral\Arch\Examples\Services\DynamoDb\User\UserModelService;

final readonly class CreateUser implements ArchAction
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): User
    {
        $this->validate($data, [
            'email' => 'required|email',
            'id' => 'required|string',
            'name' => 'required|string',
        ], []);

        return $this->userModelService->create($data);
    }

}
