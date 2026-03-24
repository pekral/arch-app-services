<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;

/**
 * Captures the aggregate results of a bulk operations demo run:
 * how many records were bulk-created, bulk-updated, insert-or-ignored,
 * and the final total user count after all operations.
 */
final class BulkOperationsResultDTO extends DataTransferObject
{

    public function __construct(public int $bulkCreateResult, public int $insertOrIgnoreResult, public int $bulkUpdateResult, public int $finalUserCount)
    {
    }

}
