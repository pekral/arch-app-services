<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\CreateUserDTO;

// Valid: a broad declared type that returns a DTO is not a response.
final readonly class ValidMixedDtoAction implements ArchAction
{

    public function __invoke(CreateUserDTO $dto): mixed
    {
        return $dto;
    }

}
