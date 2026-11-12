<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Pekral\Arch\Action\ArchAction;

abstract class AbstractForwardingAction implements ArchAction
{

    public function __invoke(): void
    {
        $this->forward();
    }

    abstract protected function forward(): void;

}

final readonly class NoInvokeAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function handle(): void
    {
        $this->auditLogger->record('handle');
    }

}

final readonly class ForwardingService
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        $this->auditLogger->record('service');
    }

}

interface ExportAction extends ArchAction
{
}
