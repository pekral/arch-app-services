<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Action\HasMiddleware;
use ReflectionClass;

test('has middleware trait returns empty array by default', function (): void {
    $action = new TestActionWithHasMiddleware();

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('middleware');
    $method->setAccessible(true);
    $result = $method->invoke($action);

    expect($result)->toBe([]);
});

final class TestActionWithHasMiddleware implements ArchAction
{

    use HasMiddleware;

}
