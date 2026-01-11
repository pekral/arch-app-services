<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use Pekral\Arch\Examples\Rules\UserValidationRules;
use Spatie\LaravelData\Attributes\Validation\Rule;

/**
 * Example DTO using shared rules from UserValidationRules.
 */
final class CreateUserWithSharedRulesDTO extends DataTransferObject
{

    public function __construct(
        #[Rule(UserValidationRules::emailRules())]
        public string $email,
        #[Rule(UserValidationRules::nameRules())]
        public string $name,
        #[Rule(UserValidationRules::phoneRules())]
        public ?string $phone = null,
    ) {
    }

}
