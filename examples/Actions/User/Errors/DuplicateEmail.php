<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Errors;

final readonly class DuplicateEmail implements UserFailure
{

    public function __construct(private string $email)
    {
    }

    public static function forEmail(string $email): self
    {
        return new self($email);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getMessage(): string
    {
        return 'User with email [' . $this->email . '] already exists.';
    }

    /**
     * @return array{
     *     type: string,
     *     message: string,
     *     email: string
     * }
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'message' => $this->getMessage(),
            'type' => 'duplicate_email',
        ];
    }

}
