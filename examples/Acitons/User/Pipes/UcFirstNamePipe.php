<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User\Pipes;

use function str;

final readonly class UcFirstNamePipe implements BuilderPipe
{

    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = str($data['name'])->lower()->ucfirst()->value();
        }

        return $next($data);
    }

}
