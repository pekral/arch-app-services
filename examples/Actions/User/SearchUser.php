<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class SearchUser
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function handle(array $filters): ?User
    {
        return $this->userModelService->findOneByParams($filters);
    }

}
