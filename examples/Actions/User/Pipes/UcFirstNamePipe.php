<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Pipes;

use Pekral\Arch\DataBuilder\BuilderPipe;

use function str;

final readonly class UcFirstNamePipe implements BuilderPipe
{

    /**
     * @param array<string, mixed> $data
     */
    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = str($data['name'])->lower()->ucfirst()->value();
        }

        return $next($data);
    }

}
