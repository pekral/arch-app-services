<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInActionsAndDataValidatorsRule;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Valid: neither an Action nor a Data Validator, so it may translate failures into HTTP exceptions.
final class NotAnActionNorValidatorService
{

    public function find(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class);

        abort(404);
    }

}
