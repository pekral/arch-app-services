<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Illuminate\Support\Str;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class TwoPropertiesAction implements ArchAction
{

    public function __construct(private AccountReader $accountReader, private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        $this->auditLogger->record($this->accountReader->accountNames()->implode(','));
    }

}

final readonly class PropertyAndTraitMethodAction implements ArchAction
{

    use DataBuilder;

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    /**
     * @param array<string, string> $data
     */
    public function __invoke(array $data): void
    {
        $this->auditLogger->record(implode(',', array_keys($this->build($data))));
    }

}

final readonly class PropertyAndStaticCallAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        $this->auditLogger->record(Str::upper('main'));
    }

}
