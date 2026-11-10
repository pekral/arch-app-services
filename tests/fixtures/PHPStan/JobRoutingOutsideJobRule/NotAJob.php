<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\JobRoutingOutsideJobRule;

final class QueueSettings
{

    public function onConnection(string $connection): self
    {
        return $connection === '' ? $this : clone $this;
    }

    public function onQueue(string $queue): self
    {
        return $queue === '' ? $this : clone $this;
    }

}

final class QueueSettingsConsumer
{

    public function configure(QueueSettings $settings): QueueSettings
    {
        return $settings->onConnection('imports')->onQueue('imports');
    }

}
