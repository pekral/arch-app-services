<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule\Actions;

// Valid: only a class has to implement ArchAction, not an interface, a trait, or an enum declared beside it.
interface ActionContract
{
}

trait ActionHelper
{
}

enum ActionType
{
    case First;
}
