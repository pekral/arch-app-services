<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

use Pekral\Arch\Data\ActionData;

interface Action
{

    public function execute(ActionData $data): mixed;

}
