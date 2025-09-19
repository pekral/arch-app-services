<?php

declare(strict_types = 1);

namespace Pekral\Arch\Validation;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;

/**
 * Trait providing validation capabilities for services.
 *
 * @mixin \Pekral\Arch\Service\BaseModelService<TModel>
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait ValidatesData
{

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function getValidationMessages(): array
    {
        return [];
    }

    /**
     * Get custom validation attributes.
     *
     * @return array<string, mixed>
     */
    public function getValidationAttributes(): array
    {
        return [];
    }

    /**
     * Validate data against rules.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, mixed> $customAttributes
     * @return array<string, mixed> Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateData(array $data, array $rules, array $messages = [], array $customAttributes = []): array
    {
        $validator = $this->createValidator($data, $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        /** @var array<string, mixed> $validatedData */
        $validatedData = $validator->validated();

        return $validatedData;
    }

    /**
     * Create a validator instance.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     * @param array<string, mixed> $customAttributes
     */
    protected function createValidator(array $data, array $rules, array $messages = [], array $customAttributes = []): Validator
    {
        return ValidatorFacade::make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Get validation rules for create operation.
     *
     * @return array<string, mixed>
     */
    protected function getCreateRules(): array
    {
        return [];
    }

    /**
     * Get validation rules for update operation.
     *
     * @return array<string, mixed>
     */
    protected function getUpdateRules(): array
    {
        return [];
    }

}
