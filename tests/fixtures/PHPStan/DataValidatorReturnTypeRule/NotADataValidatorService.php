<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators;

// Valid: not a Data Validator, so its public methods may return anything.
final class NotADataValidatorService
{

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return [];
    }

}
