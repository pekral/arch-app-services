<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

/**
 * Base interface for action implementations.
 *
 * Actions represent single-purpose operations that orchestrate business logic.
 * Each action class must expose exactly one public instance entry point: `__invoke()`.
 * No other public methods are allowed (besides the constructor).
 *
 * Actions should be focused on a single responsibility and coordinate between
 * services, repositories, and other components to accomplish their goal.
 */
interface ArchAction
{

}
