<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Illuminate\Pagination\LengthAwarePaginator;
use Pekral\Arch\Examples\Services\User\UserModelService;

final readonly class GetUsers
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, \Pekral\Arch\Tests\Models\User>
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        /** @var \Illuminate\Pagination\LengthAwarePaginator<int, \Pekral\Arch\Tests\Models\User> $paginator */
        $paginator = $this->userModelService->paginateByParams($filters);
        
        return $paginator;
    }

}
