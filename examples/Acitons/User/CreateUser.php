<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User;

use Pekral\Arch\Action\Action;
use Pekral\Arch\Data\ActionData;
use Pekral\Arch\Examples\Acitons\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Acitons\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Service\DataBuilder;
use Pekral\Arch\Tests\Models\User;

final readonly class CreateUser implements Action
{

    public function __construct(private UserModelService $userModelService, private DataBuilder $baseDataBuilder)
    {
    }

    public function execute(ActionData $data): User
    {

        $dataNormalized = $this->baseDataBuilder->build($data, [LowercaseEmailPipe::class, UcFirstNamePipe::class]);

        return $this->userModelService->create($dataNormalized);
    }

}
