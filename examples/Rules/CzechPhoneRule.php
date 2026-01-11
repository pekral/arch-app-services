<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use function assert;

final class CzechPhoneRule implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assert($attribute !== '');

        if (!is_string($value) || !preg_match('/^\+420\d{9}$/', $value)) {
            $fail('The phone number must be in format +420XXXXXXXXX.');
        }
    }

}
