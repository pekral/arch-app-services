<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

use Throwable;

/**
 * @implements \Pekral\Arch\Action\ActionMiddleware<mixed>
 */
final readonly class LoggingMiddleware implements ActionMiddleware
{

    public function __construct(private ActionLoggerService $actionLoggerService)
    {
    }

    public function handle(ArchAction $action, callable $next): mixed
    {
        $actionName = $action::class;
        $this->actionLoggerService->logActionStart($actionName);
        
        try {
            $result = $next();
            $this->actionLoggerService->logActionSuccess($actionName);

            return $result;
        } catch (Throwable $e) {
            $this->actionLoggerService->logActionFailure($actionName, $e->getMessage());

            throw $e;
        }
    }

}
