<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use Pekral\Arch\Action\ActionExecutor;
use Pekral\Arch\Action\ActionLoggerService;
use Pekral\Arch\Action\ActionMiddleware;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Action\HasMiddleware;
use Pekral\Arch\Action\LoggingMiddleware;
use RuntimeException;

test('action executor executes action without middleware', function (): void {
    $container = app(Container::class);
    $executor = new ActionExecutor($container);
    $action = new TestActionWithoutMiddleware();
    
    $result = $executor->execute($action, ['test' => 'value']);
    
    expect($result)->toBe('value');
});

test('action executor executes action with middleware', function (): void {
    $container = app(Container::class);
    $executor = new ActionExecutor($container);
    $action = new TestActionWithMiddleware();
    Log::spy();
    
    $result = $executor->execute($action, ['test' => 'value']);
    
    expect($result)->toBe('value');
    Log::shouldHaveReceived('channel')
        ->twice()
        ->with('stack');
});

test('action executor executes action with multiple middleware', function (): void {
    $container = app(Container::class);
    $executor = new ActionExecutor($container);
    $action = new TestActionWithMultipleMiddleware();
    Log::spy();
    
    $result = $executor->execute($action, ['test' => 'value']);
    
    expect($result)->toBe('value');
    Log::shouldHaveReceived('channel')
        ->times(4);
});

test('action executor handles exceptions through middleware', function (): void {
    $container = app(Container::class);
    $executor = new ActionExecutor($container);
    $action = new TestActionWithMiddlewareThatThrows();
    Log::spy();
    
    expect(fn (): mixed => $executor->execute($action, ['test' => 'value']))
        ->toThrow(RuntimeException::class, 'Action error for parameter: value');
    
    Log::shouldHaveReceived('channel')
        ->twice()
        ->with('stack');
});

test('action executor passes parameters correctly to execute', function (): void {
    $container = app(Container::class);
    $executor = new ActionExecutor($container);
    $action = new TestActionWithMultipleParameters();
    
    $result = $executor->execute($action, ['param1' => 'value1', 'param2' => 'value2']);
    
    expect($result)->toBe('value1-value2');
});

final class TestActionWithoutMiddleware implements ArchAction
{

    public function execute(string $test): string
    {
        return $test;
    }

}

final class TestActionWithMiddleware implements ArchAction
{

    use HasMiddleware;

    public function execute(string $test): string
    {
        return $test;
    }

    protected function middleware(): array
    {
        return [
            LoggingMiddleware::class,
        ];
    }

}

final class TestActionWithMultipleMiddleware implements ArchAction
{

    use HasMiddleware;

    public function execute(string $test): string
    {
        return $test;
    }

    protected function middleware(): array
    {
        return [
            LoggingMiddleware::class,
            TestMiddleware::class,
        ];
    }

}

final class TestActionWithMiddlewareThatThrows implements ArchAction
{

    use HasMiddleware;

    public function execute(string $test): never
    {
        throw new RuntimeException('Action error for parameter: ' . $test);
    }

    protected function middleware(): array
    {
        return [
            LoggingMiddleware::class,
        ];
    }

}

final class TestActionWithMultipleParameters implements ArchAction
{

    public function execute(string $param1, string $param2): string
    {
        return $param1 . '-' . $param2;
    }

}

final readonly class TestMiddleware implements ActionMiddleware
{

    public function __construct(private ActionLoggerService $actionLoggerService)
    {
    }

    public function handle(ArchAction $action, callable $next): mixed
    {
        $this->actionLoggerService->logActionStart($action::class);
        $result = $next();
        $this->actionLoggerService->logActionSuccess($action::class);
        
        return $result;
    }

}
