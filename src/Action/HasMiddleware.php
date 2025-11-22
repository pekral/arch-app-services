<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

trait HasMiddleware
{

    /**
     * @return array<int, class-string<\Pekral\Arch\Action\ActionMiddleware>>
     */
    protected function middleware(): array
    {
        return [];
    }

}
