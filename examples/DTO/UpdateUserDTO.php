<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class UpdateUserDTO extends DataTransferObject
{

    public function __construct(
        #[ Email, Required]
        public string $email,
        #[ Max(255), Required]
        public string $name,
        #[Max(20)]
        public ?string $phone = null,
    ) {
    }

}
