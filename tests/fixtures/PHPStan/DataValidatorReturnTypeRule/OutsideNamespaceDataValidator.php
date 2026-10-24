<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Elsewhere;

// Valid: a Data Validator outside the namespaces the test configuration limits the rule to.
final class OutsideNamespaceDataValidator
{

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return [];
    }

}
