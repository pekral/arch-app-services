<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User\Pipes;

final readonly class UcfirstNamePipe implements UserDataPipe
{

    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = ucfirst($data['name']);
        }

        return $next($data);
    }

}
