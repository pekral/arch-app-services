<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataValidation;

use Illuminate\Support\Facades\Validator;

/**
 * Trait for validating data using Laravel's validation system.
 *
 * Provides a convenient method to validate data arrays against validation rules.
 * Throws ValidationException if validation fails, otherwise returns the validated data.
 *
 * Usage:
 * $validated = $this->validate($data, [
 *     'email' => 'required|email',
 *     'name' => 'required|string|max:255',
 * ], [
 *     'email.required' => 'Email is required',
 * ]);
 */
trait DataValidator
{

    /**
     * Validate data against validation rules.
     *
     * @param array<string, mixed> $data Data to validate
     * @param array<string, mixed> $rules Validation rules (e.g., ['email' => 'required|email'])
     * @param array<string, mixed> $messages Custom validation error messages
     * @return array<string, mixed> Validated and sanitized data
     * @throws \Illuminate\Validation\ValidationException When validation fails
     */
    public function validate(mixed $data, array $rules, array $messages): array
    {
        /** @var array<string, mixed> $result */
        $result = Validator::make($data, $rules, $messages)
            ->validate();
        
        return $result;
    }

}
