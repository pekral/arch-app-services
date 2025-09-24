<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Acitons\User\DataBuilder;

use Pekral\Arch\Examples\Acitons\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Acitons\User\Pipes\UcfirstNamePipe;
use Pekral\Arch\Service\BaseDataBuilder;

/**
 * Data builder for user data transformation.
 *
 * Transforms user data using specific pipes for user creation.
 *
 * @extends \Pekral\Arch\Service\BaseDataBuilder<array{email: string, name: string, password: string}>
 */
final class UserDataBuilder extends BaseDataBuilder
{

    public function getPipes(): array
    {
        return [
            LowercaseEmailPipe::class,
            UcfirstNamePipe::class,
        ];
    }

}
