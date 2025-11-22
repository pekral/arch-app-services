<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

use Illuminate\Container\Container;
use ReflectionClass;

final readonly class ActionExecutor
{

    public function __construct(private Container $container)
    {
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $parameters
     */
    public function execute(ArchAction $action, array $parameters = []): mixed
    {
        if (!$this->hasMiddleware($action)) {
            return $this->callExecute($action, $parameters);
        }

        $middleware = $this->resolveMiddleware($action);
        
        return $this->runThroughMiddleware($action, $middleware, $parameters);
    }

    private function hasMiddleware(ArchAction $action): bool
    {
        return in_array(HasMiddleware::class, class_uses_recursive($action::class), true);
    }

    /**
     * @return array<int, \Pekral\Arch\Action\ActionMiddleware<mixed>>
     */
    private function resolveMiddleware(ArchAction $action): array
    {
        $reflection = new ReflectionClass($action);
        $method = $reflection->getMethod('middleware');
        $method->setAccessible(true);
        /** @var array<int, class-string<\Pekral\Arch\Action\ActionMiddleware<mixed>>> $middlewareClasses */
        $middlewareClasses = $method->invoke($action);
        
        /** @var array<int, \Pekral\Arch\Action\ActionMiddleware<mixed>> $middleware */
        $middleware = [];

        foreach ($middlewareClasses as $middlewareClass) {
            /** @var \Pekral\Arch\Action\ActionMiddleware<mixed> $instance */
            $instance = $this->container->make($middlewareClass);
            $middleware[] = $instance;
        }
        
        return $middleware;
    }

    /**
     * @param array<int, \Pekral\Arch\Action\ActionMiddleware<mixed>> $middleware
     * @param array<string, mixed>|array<int, mixed> $parameters
     */
    private function runThroughMiddleware(ArchAction $action, array $middleware, array $parameters): mixed
    {
        $next = (fn (): mixed => $this->callExecute($action, $parameters));

        foreach (array_reverse($middleware) as $middlewareInstance) {
            $next = (fn () => $middlewareInstance->handle($action, $next));
        }

        return $next();
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $parameters
     */
    private function callExecute(ArchAction $action, array $parameters): mixed
    {
        /** @var callable $callable */
        $callable = [$action, 'execute'];
        /** @var array<string, mixed> $namedParameters */
        $namedParameters = $parameters;
        
        return $this->container->call($callable, $namedParameters);
    }

}
