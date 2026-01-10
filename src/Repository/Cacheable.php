<?php

declare(strict_types = 1);

namespace Pekral\Arch\Repository;

interface Cacheable
{

    public function cache(?string $driver = null): CacheWrapper;

}
