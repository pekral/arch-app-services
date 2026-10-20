<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInDataValidatorsRule\Validators;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Intentionally throws HTTP exceptions to trigger NoHttpExceptionsInDataValidatorsRule.
final class VisibilityDataValidator
{

    public function validate(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class);
    }

}

final class InstanceDataValidator
{

    public function validate(bool $condition): void
    {
        throw_if($condition, new NotFoundHttpException('Not found.'));
    }

}

// Valid: a validation exception is the sanctioned failure of a Data Validator.
final class PayloadDataValidator
{

    public function validate(bool $condition): void
    {
        throw_if($condition, ValidationException::class);
    }

}
