<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class SearchUserCached implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters): ?User
    {
        $repository = $this->userModelService->getRepository();
        $cacheWrapper = $repository->cache();

        /** @var ?\Pekral\Arch\Tests\Models\User $result */
        $result = $cacheWrapper->findOneByParams($filters);

        return $result;
    }

}
