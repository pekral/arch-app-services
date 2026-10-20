<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoManualArchitectureServiceInstantiationRule;

use ArrayObject;
use Pekral\Arch\Examples\DTO\CreateUserDTO;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Tests\Models\User;

final readonly class InjectedDependencies
{

    public function __construct(private UserModelService $userModelService)
    {
    }

    public function createDto(): CreateUserDTO
    {
        return new CreateUserDTO(email: 'john@example.com', name: 'John Doe');
    }

    /**
     * @return \ArrayObject<int, string>
     */
    public function createValueObject(): ArrayObject
    {
        return new ArrayObject(['CZK']);
    }

    public function createModel(): User
    {
        return new User(['name' => $this->userModelService::class]);
    }

    /**
     * @return \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
     */
    public function createAnonymousRepository(): BaseRepository
    {
        return new
        /**
         * @extends \Pekral\Arch\Repository\Mysql\BaseRepository<\Pekral\Arch\Tests\Models\User>
         */
        readonly class extends BaseRepository {

            protected function getModelClassName(): string
            {
                return User::class;
            }

        };
    }

}
