<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Pekral\Arch\Action\ArchAction;

final readonly class DispatchAccountsAction implements ArchAction
{

    public function __construct(private AccountReader $accountReader)
    {
    }

    public function __invoke(): void
    {
        $this->accountReader
            ->accountNames()
            ->each(static function (string $accountName): void {
                dispatch(new ExpireCouponsJob(strtoupper($accountName)));
            });
    }

}

final readonly class ForeachDispatchAction implements ArchAction
{

    public function __construct(private AccountReader $accountReader)
    {
    }

    public function __invoke(): void
    {
        foreach ($this->accountReader->accountNames() as $accountName) {
            dispatch(new ExpireCouponsJob($accountName));
        }
    }

}

final readonly class ForwardingAction implements ArchAction
{

    public function __construct(private DispatchAccountsAction $dispatchAccountsAction)
    {
    }

    public function __invoke(): void
    {
        ($this->dispatchAccountsAction)();
    }

}

final readonly class PrivateHelperAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        $this->recordStart();
    }

    private static function label(): string
    {
        return 'start';
    }

    private function recordStart(): void
    {
        $this->auditLogger->record(self::label());
    }

}

final readonly class NoCollaboratorAction implements ArchAction
{

    public function __invoke(): string
    {
        return strtoupper('done');
    }

}
