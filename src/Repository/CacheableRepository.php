<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

trait CacheableRepository
{

    public function cache(?string $driver = null): CacheWrapper
    {
        return new CacheWrapper($this, $driver);
    }

}
