<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\Services\User\UserModelService;

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
        
        // @phpstan-ignore-next-line
        return $this->userModelService->getRepository()->cache()->countByParams($params);
    }

}
