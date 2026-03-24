<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionReturnDtoRule;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Examples\DTO\CreateUserDTO;

// Valid — returning a DTO (extending Spatie\LaravelData\Data) is allowed.
final readonly class ValidDtoAction implements ArchAction
{

    public function __invoke(): CreateUserDTO
    {
        return new CreateUserDTO(email: 'test@example.com', name: 'Test');
    }

}
