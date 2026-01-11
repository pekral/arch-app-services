<?php

declare(strict_types = 1);

namespace Pekral\Arch\DataValidation;

interface ValidationRules
{

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array;

}
