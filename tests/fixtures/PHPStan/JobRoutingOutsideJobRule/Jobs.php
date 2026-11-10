<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\JobRoutingOutsideJobRule;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

trait RoutesTenantGroup
{

    public function onTenantGroup(int $tenantId): static
    {
        $this->onGroup(sprintf('tenant-%d', $tenantId));

        return $this;
    }

}

final class ImportSubscribersJob implements ShouldQueue
{

    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use RoutesTenantGroup;

    public function __construct(private readonly string $fileUrl, private readonly int $listId, string $dbHost)
    {
        $this->onConnection('imports');
        $this->onQueue('imports');
        $this->onGroup(sprintf('%s:%d', $dbHost, $listId % 4));
    }

    public function handle(): void
    {
        if ($this->fileUrl === '' || $this->listId < 1) {
            return;
        }

        dispatch(new self($this->fileUrl, $this->listId + 1, 'db-1'))->onConnection('imports');
    }

}

final class PropertyRoutedJob implements ShouldQueue
{

    use Dispatchable;

    public ?string $connection = 'imports';

    public ?string $queue = 'imports';

    public function handle(): void
    {
    }

}
