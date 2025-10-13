<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataValidation;

use Illuminate\Support\Facades\Validator;

trait DataValidator
{

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, mixed> $messages
     * @return array<string, mixed>
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(mixed $data, array $rules, array $messages): array
    {
        /** @var array<string, mixed> $result */
        $result = Validator::make($data, $rules, $messages)
            ->validate();
        
        return $result;
    }

}
