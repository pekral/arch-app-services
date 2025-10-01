<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\Action;
use Pekral\Arch\Data\ActionData;
use Pekral\Arch\Examples\Actions\User\Data\UserActionData;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Service\DataBuilder;

final readonly class UpdateUser implements Action
{

    public function __construct(private DataBuilder $dataBuilder, private UserModelService $userModelService)
    {
    }

    public function execute(UserActionData|ActionData $data): int
    {
        if (!$data instanceof UserActionData || !$data->id) {
            return 0;
        }

        $dataNormalized = $this->dataBuilder->build($data, [LowercaseEmailPipe::class, UcFirstNamePipe::class]);

        return $this->userModelService->updateByParams($dataNormalized, ['id' => $data->id]);
    }

}
