<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Illuminate\Support\Facades\Log;
use Pekral\Arch\Action\ActionLoggerService;
use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\Action\LoggingMiddleware;
use RuntimeException;

test('logging middleware logs action start and success', function (): void {
    $logger = new ActionLoggerService();
    $middleware = new LoggingMiddleware($logger);
    $action = new TestAction();
    Log::spy();
    
    $result = $middleware->handle($action, fn (): string => 'result');
    
    expect($result)->toBe('result');
    Log::shouldHaveReceived('channel')
        ->twice()
        ->with('stack');
});

test('logging middleware logs action failure on exception', function (): void {
    $logger = new ActionLoggerService();
    $middleware = new LoggingMiddleware($logger);
    $action = new TestAction();
    Log::spy();
    
    try {
        $middleware->handle($action, function (): never {
            throw new RuntimeException('Test error');
        });
        expect(false)->toBeTrue();
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Test error');
    }
    
    Log::shouldHaveReceived('channel')
        ->twice()
        ->with('stack');
});

test('logging middleware rethrows exception after logging', function (): void {
    $logger = new ActionLoggerService();
    $middleware = new LoggingMiddleware($logger);
    $action = new TestAction();
    $exception = new RuntimeException('Test error');
    
    expect(fn (): mixed => $middleware->handle($action, function () use ($exception): never {
        throw $exception;
    }))->toThrow(RuntimeException::class, 'Test error');
});

final class TestAction implements ArchAction
{

}
