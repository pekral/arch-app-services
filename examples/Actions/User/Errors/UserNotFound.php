<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User\Errors;

final readonly class UserNotFound implements UserFailure
{

    public function __construct(private int|string $identifier)
    {
    }

    public static function withId(int|string $id): self
    {
        return new self($id);
    }

    public function getIdentifier(): int|string
    {
        return $this->identifier;
    }

    public function getMessage(): string
    {
        return 'User with identifier [' . $this->identifier . '] was not found.';
    }

    /**
     * @return array{
     *     type: string,
     *     message: string,
     *     identifier: int|string
     * }
     */
    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'message' => $this->getMessage(),
            'type' => 'not_found',
        ];
    }

}
