<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Pipes;

use Pekral\Arch\DataBuilder\BuilderPipe;

final readonly class LowercaseEmailPipe implements BuilderPipe
{

    /**
     * @param array<string, mixed> $data
     */
    public function handle(array $data, callable $next): array
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        return $next($data);
    }

}
