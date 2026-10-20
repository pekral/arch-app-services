<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule;

final class NotAnArchitectureClass
{

    public static int $counter = 0;

}
