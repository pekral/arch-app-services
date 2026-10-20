<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule;

use Pekral\Arch\DTO\DataTransferObject;

final class PublicPropertiesDto extends DataTransferObject
{

    public function __construct(public string $email, public string $name)
    {
    }

}
