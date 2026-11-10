<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\JobRoutingOutsideJobRule;

final class ImportController
{

    public function __invoke(string $fileUrl, int $listId): void
    {
        dispatch(new ImportSubscribersJob($fileUrl, $listId, 'db-1'))->onConnection('imports');

        ImportSubscribersJob::dispatch($fileUrl, $listId, 'db-1')->onQueue('imports');

        $job = new ImportSubscribersJob($fileUrl, $listId, 'db-1');
        $job->onGroup('group-1');
        $job->onTenantGroup($listId);

        ImportSubscribersJob::dispatch($fileUrl, $listId, 'db-1')->delay(60);
        PropertyRoutedJob::dispatch();
    }

}
