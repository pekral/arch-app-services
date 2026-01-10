<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Pekral\Arch\Examples\Rules\UserValidationRules;

final class CreateUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return UserValidationRules::rules();
    }

}
