<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionReturnDtoRule;

use Pekral\Arch\Action\ArchAction;

// Intentionally returns array to trigger ActionReturnDtoRule.
final readonly class ArrayReturnAction implements ArchAction
{

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [];
    }

}
