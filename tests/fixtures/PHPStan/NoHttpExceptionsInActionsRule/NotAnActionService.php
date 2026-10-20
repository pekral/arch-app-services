<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInActionsRule;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Valid: not an Action, so it may translate failures into HTTP exceptions.
final class NotAnActionService
{

    public function find(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class);

        abort(404);
    }

}
