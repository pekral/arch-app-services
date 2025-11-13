<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;

final readonly class BulkDeleteUsersByParams implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function execute(array $parameters): void
    {
        $this->userModelService->bulkDeleteByParams($parameters);
    }

}
