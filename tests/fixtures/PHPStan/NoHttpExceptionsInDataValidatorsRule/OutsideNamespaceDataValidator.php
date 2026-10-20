<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInDataValidatorsRule\Elsewhere;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Valid: a Data Validator outside the namespaces the test configuration limits the rule to.
final class OutsideNamespaceDataValidator
{

    public function validate(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class);
    }

}
