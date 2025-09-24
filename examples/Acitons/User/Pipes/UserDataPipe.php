<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User\Pipes;

/**
 * Interface for user data transformation pipes.
 */
interface UserDataPipe
{

    /**
     * Transform user data.
     *
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): array<string, mixed> $next
     * @return array<string, mixed>
     */
    public function handle(array $data, callable $next): array;

}
