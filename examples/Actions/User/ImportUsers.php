<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Examples\Services\User\UserModelService;

final readonly class ImportUsers
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @template TKey of array-key
     * @template TValue
     * @param array<int, array<TKey, TValue>> $data
     */
    public function handle(array $data): int
    {
        return $this->userModelService->bulkCreate($data);
    }

}
