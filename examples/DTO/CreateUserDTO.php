<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use Pekral\Arch\Examples\Rules\CzechPhoneRule;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;

final class CreateUserDTO extends DataTransferObject
{

    public function __construct(
        #[Email, Required]
        public string $email,
        #[Max(255), Required]
        public string $name,
        #[Nullable, Rule(new CzechPhoneRule())]
        public ?string $phone = null,
    ) {
    }

}
