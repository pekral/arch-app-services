<?php

declare(strict_types = 1);

namespace Pekral\Arch\Examples\DTO;

use Pekral\Arch\DTO\DataTransferObject;

/**
 * Represents the outcome of a bulk user import operation,
 * summarising how many records were created, ignored, and processed in total.
 */
final class BulkImportResultDTO extends DataTransferObject
{

    public function __construct(public int $totalProcessed, public int $created, public int $ignored,) {
    }

}
