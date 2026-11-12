<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Transaction\InTransaction;
use Pekral\Arch\Transaction\Transactional;
use RuntimeException;

final readonly class IfAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        if ($verbose) {
            $this->auditLogger->record('verbose');
        }
    }

}

final readonly class MatchAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(int $level): void
    {
        $this->auditLogger->record(match ($level) {
            1 => 'low',
            default => 'high',
        });
    }

}

final readonly class TernaryAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        $this->auditLogger->record($verbose ? 'verbose' : 'quiet');
    }

}

final readonly class CoalesceAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(?string $message): void
    {
        $this->auditLogger->record($message ?? 'empty');
    }

}

final readonly class BooleanOperatorAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose, bool $enabled): void
    {
        $verbose && $enabled && $this->auditLogger->record('verbose');
    }

}

final readonly class WhileAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(int $attempts): void
    {
        while ($attempts-- > 0) {
            $this->auditLogger->record('attempt');
        }
    }

}

final readonly class TryCatchAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        try {
            $this->auditLogger->record('try');
        } catch (RuntimeException) {
            echo 'failed';
        }
    }

}

final readonly class ThrowAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): never
    {
        $this->auditLogger->record('fail');

        throw new LogicException('Not supported.');
    }

}

final readonly class TransactionAction implements ArchAction
{

    use Transactional;

    public function __invoke(): void
    {
        $this->transaction(static function (): void {
            dispatch(new ExpireCouponsJob('main'));
        });
    }

}

final readonly class DatabaseTransactionAction implements ArchAction
{

    public function __invoke(): void
    {
        DB::transaction(static function (): void {
            dispatch(new ExpireCouponsJob('main'));
        });
    }

}

final readonly class InTransactionAttributeAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    #[InTransaction]
    public function __invoke(): void
    {
        $this->auditLogger->record('in transaction');
    }

}

final readonly class ThrowIfAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        throw_if($verbose, LogicException::class);
        $this->auditLogger->record('checked');
    }

}

final readonly class ThrowUnlessAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        throw_unless($verbose, LogicException::class);
        $this->auditLogger->record('checked');
    }

}

final readonly class AbortIfAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        abort_if($verbose, 404);
        $this->auditLogger->record('checked');
    }

}

final readonly class AbortUnlessAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(bool $verbose): void
    {
        abort_unless($verbose, 404);
        $this->auditLogger->record('checked');
    }

}

final readonly class RescueAction implements ArchAction
{

    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function __invoke(): void
    {
        rescue(fn (): bool => true);
        $this->auditLogger->record('checked');
    }

}

final readonly class WhenAction implements ArchAction
{

    public function __construct(private AccountReader $accountReader)
    {
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function __invoke(bool $verbose): Collection
    {
        return $this->accountReader->accountNames()->when($verbose, static fn (Collection $names): Collection => $names->take(1));
    }

}

final readonly class UnlessAction implements ArchAction
{

    public function __construct(private AccountReader $accountReader)
    {
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function __invoke(bool $verbose): Collection
    {
        return $this->accountReader->accountNames()->unless($verbose, static fn (Collection $names): Collection => $names->take(1));
    }

}
