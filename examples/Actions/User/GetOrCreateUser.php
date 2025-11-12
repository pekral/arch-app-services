<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;
use Pekral\Arch\Examples\Actions\User\Pipes\LowercaseEmailPipe;
use Pekral\Arch\Examples\Actions\User\Pipes\UcFirstNamePipe;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

final readonly class GetOrCreateUser
{

    use DataBuilder;
    use DataValidator;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $attributes Attributes to search for
     * @param array<string, mixed> $values Values to use when creating
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $attributes, array $values = []): User
    {
        $mergedData = [...$attributes, ...$values];
        
        $this->validate($mergedData, [
            'email' => 'required|email',
            'name' => 'required|string',
        ], []);
        
        $attributesNormalized = $this->build(
            $attributes,
            [
                'email' => LowercaseEmailPipe::class,
            ],
        );
        
        $valuesNormalized = $this->build(
            $values,
            [
                'email' => LowercaseEmailPipe::class,
                'name' => UcFirstNamePipe::class,
            ],
        );

        return $this->userModelService->getOrCreate($attributesNormalized, $valuesNormalized);
    }

}
