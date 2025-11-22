<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

/**
 * Base interface for action implementations.
 *
 * Actions represent single-purpose operations that orchestrate business logic.
 * Each action class must implement a single public `execute()` method that serves
 * as the entry point for the action's functionality.
 *
 * Actions should be focused on a single responsibility and coordinate between
 * services, repositories, and other components to accomplish their goal.
 */
interface ArchAction
{

}
