<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule\Legacy;

use Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule\AuditLogger;

final readonly class LegacyForwardingAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        $this->auditLogger->record('legacy');
    }

}
