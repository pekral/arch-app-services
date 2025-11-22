<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;

use function assert;
use function is_int;

final readonly class CountVerifiedUsersCached implements ArchAction
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function handle(): int
    {
        /** @var array<int, array<int, mixed>> $params */
        $params = [
            ['email_verified_at', '!=', null],
        ];

        $repository = $this->userModelService->getRepository();
        $cacheWrapper = $repository->cache();

        $result = $cacheWrapper->countByParams($params);
        assert(is_int($result));

        return $result;
    }

}
