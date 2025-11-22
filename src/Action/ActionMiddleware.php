<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

/**
 * @template TReturn
 */
interface ActionMiddleware
{

    /**
     * @param callable(): TReturn $next
     * @return TReturn
     */
    public function handle(ArchAction $action, callable $next): mixed;

}
