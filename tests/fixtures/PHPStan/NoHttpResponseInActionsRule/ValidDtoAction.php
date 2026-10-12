<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\CreateUserDTO;

// Valid: a spatie Data DTO is Responsable but is the sanctioned result.
final readonly class ValidDtoAction implements ArchAction
{

    public function __invoke(): CreateUserDTO
    {
        throw new \LogicException('Fixture only.');
    }

}
