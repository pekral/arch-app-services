<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Rules;

use Pekral\Arch\DataValidation\ValidationRules;

/**
 * Centralized validation rules for User entity.
 * Can be used in both Request and DTO.
 */
final class UserValidationRules implements ValidationRules
{

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => self::emailRules(),
            'name' => self::nameRules(),
            'phone' => self::phoneRules(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function emailRules(): array
    {
        return ['required', 'email'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function nameRules(): array
    {
        return ['required', 'max:255'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function phoneRules(): array
    {
        return ['nullable', new CzechPhoneRule()];
    }

}
