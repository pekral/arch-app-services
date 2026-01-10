<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Errors;

/**
 * Base error interface for user-related operations.
 */
interface UserFailure
{

    public function getMessage(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

}
