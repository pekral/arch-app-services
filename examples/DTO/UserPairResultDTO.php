<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use Pekral\Arch\Tests\Models\User;

/**
 * Holds the result of a transaction that creates or updates two users —
 * a required primary and an optional secondary.
 */
final class UserPairResultDTO extends DataTransferObject
{

    public function __construct(public User $primary, public ?User $secondary = null)
    {
    }

}
