<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInDataValidatorsRule\Validators;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Valid: not a Data Validator, so it may translate failures into HTTP exceptions.
final class NotADataValidatorService
{

    public function find(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class);

        abort(404);
    }

}
