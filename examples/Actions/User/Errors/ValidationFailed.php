<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Errors;

use Illuminate\Validation\ValidationException;

final readonly class ValidationFailed implements UserFailure
{

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(private array $errors)
    {
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    public static function fromErrors(array $errors): self
    {
        return new self($errors);
    }

    public static function fromException(ValidationException $exception): self
    {
        /** @var array<string, array<int, string>> $errors */
        $errors = $exception->errors();

        return new self($errors);
    }

    public function getMessage(): string
    {
        $messages = [];

        foreach ($this->errors as $field => $fieldErrors) {
            $messages[] = $field . ': ' . implode(', ', $fieldErrors);
        }

        return implode('; ', $messages);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array{
     *     type: string,
     *     message: string,
     *     errors: array<string, array<int, string>>
     * }
     */
    public function toArray(): array
    {
        return [
            'errors' => $this->errors,
            'message' => $this->getMessage(),
            'type' => 'validation',
        ];
    }

}
