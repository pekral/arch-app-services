<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

use function assert;

final readonly class GetUserCached implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters): User
    {
        $repository = $this->userModelService->getRepository();
        $cacheWrapper = $repository->cache();

        $result = $cacheWrapper->getOneByParams($filters);
        assert($result instanceof User);

        return $result;
    }

}
