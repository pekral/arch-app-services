<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInActionsAndDataValidatorsRule\Legacy;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Does not implement ArchAction, but lives in the namespace the test configuration lists as an Action namespace.
final readonly class LegacyNamespaceAction
{

    public function __invoke(): never
    {
        throw new NotFoundHttpException('Not found.');
    }

}
