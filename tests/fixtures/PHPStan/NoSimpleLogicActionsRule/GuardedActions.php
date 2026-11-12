<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataValidation\DataValidator;

final readonly class DataValidatorTraitAction implements ArchAction
{

    use DataValidator;

    /**
     * @param array<string, string> $data
     */
    public function __invoke(array $data): void
    {
        $this->validate($data, ['name' => 'required'], []);
    }

}

final readonly class DataValidatorCollaboratorAction implements ArchAction
{

    public function __construct(private AccountDataValidator $accountDataValidator)
    {
    }

    public function __invoke(): void
    {
        $this->accountDataValidator->assertActive('main');
    }

}

final readonly class ValidatorFacadeAction implements ArchAction
{

    /**
     * @param array<string, string> $data
     */
    public function __invoke(array $data): void
    {
        Validator::make($data, ['name' => 'required'])->stopOnFirstFailure();
    }

}

final readonly class AuthorizeAction implements ArchAction
{

    public function __construct(private GateContract $gate)
    {
    }

    public function __invoke(): void
    {
        $this->gate->authorize('export-accounts');
    }

}

final readonly class GateFacadeAction implements ArchAction
{

    public function __invoke(): void
    {
        Gate::inspect('export-accounts');
    }

}
