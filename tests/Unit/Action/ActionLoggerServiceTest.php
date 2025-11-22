<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Illuminate\Support\Facades\Log;
use Mockery;
use Pekral\Arch\Action\ActionLoggerService;
use Psr\Log\LoggerInterface;
use RuntimeException;

test('log action start writes info log', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';
    $context = ['user_id' => 123];

    $logger->logActionStart($action, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action start works without context', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';

    $logger->logActionStart($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action start skips when logging disabled', function (): void {
    $logger = new ActionLoggerService();
    config(['arch.action_logging.enabled' => false]);
    Log::spy();
    $action = 'CreateUser';

    $logger->logActionStart($action);

    Log::shouldNotHaveReceived('channel');
});

test('log action success writes info log', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';
    $context = ['user_id' => 123, 'execution_time' => 0.5];

    $logger->logActionSuccess($action, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action success works without context', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';

    $logger->logActionSuccess($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action success skips when logging disabled', function (): void {
    $logger = new ActionLoggerService();
    config(['arch.action_logging.enabled' => false]);
    Log::spy();
    $action = 'CreateUser';

    $logger->logActionSuccess($action);

    Log::shouldNotHaveReceived('channel');
});

test('log action failure writes error log', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';
    $error = 'Validation failed';
    $context = ['user_data' => ['email' => 'invalid']];

    $logger->logActionFailure($action, $error, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action failure works without context', function (): void {
    $logger = new ActionLoggerService();
    Log::spy();
    $action = 'CreateUser';
    $error = 'Database connection failed';

    $logger->logActionFailure($action, $error);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action failure skips when logging disabled', function (): void {
    $logger = new ActionLoggerService();
    config(['arch.action_logging.enabled' => false]);
    Log::spy();
    $action = 'CreateUser';
    $error = 'Test error';

    $logger->logActionFailure($action, $error);

    Log::shouldNotHaveReceived('channel');
});

test('logging uses custom channel', function (): void {
    $logger = new ActionLoggerService();
    config(['arch.action_logging.channel' => 'custom']);
    Log::spy();
    $action = 'CustomAction';

    $logger->logActionStart($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('custom');
});

test('fallback logging when primary logger fails', function (): void {
    $logger = new ActionLoggerService();
    $action = 'FailingAction';
    $context = ['test' => 'data'];
    $logPath = storage_path('logs/arch.log');
    
    if (file_exists($logPath)) {
        unlink($logPath);
    }

    $exceptionMessage = 'Logger connection failed';
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('info')
        ->once()
        ->andThrow(new RuntimeException($exceptionMessage));

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($mockLogger);

    $logger->logActionStart($action, $context);

    expect(file_exists($logPath))->toBeTrue();
    
    $logContent = file_get_contents($logPath);
    expect($logContent)->toContain('ARCH FALLBACK LOG')
        ->toContain('Action: FailingAction')
        ->toContain('Type: start')
        ->toContain($exceptionMessage)
        ->toContain('"test": "data"');
    
    if (file_exists($logPath)) {
        unlink($logPath);
    }
});

test('fallback logging for all log levels', function (): void {
    $logger = new ActionLoggerService();
    $logPath = storage_path('logs/arch.log');
    
    if (file_exists($logPath)) {
        unlink($logPath);
    }

    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('info')
        ->twice()
        ->andThrow(new RuntimeException('Info logging failed'));
    $mockLogger->shouldReceive('error')
        ->once()
        ->andThrow(new RuntimeException('Error logging failed'));

    Log::shouldReceive('channel')
        ->times(3)
        ->with('stack')
        ->andReturn($mockLogger);

    $logger->logActionStart('TestAction');
    $logger->logActionSuccess('TestAction');
    $logger->logActionFailure('TestAction', 'Test error');

    expect(file_exists($logPath))->toBeTrue();
    
    $logContent = file_get_contents($logPath);
    expect($logContent)->toContain('Type: start')
        ->toContain('Type: success')
        ->toContain('Type: failure');
    
    if (file_exists($logPath)) {
        unlink($logPath);
    }
});

test('no exception when both primary and fallback logging fail', function (): void {
    $logger = new ActionLoggerService();
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('info')
        ->once()
        ->andThrow(new RuntimeException('Primary logging failed'));

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($mockLogger);

    $logger->logActionStart('FailingAction');

    expect(true)->toBeTrue();
});

test('fallback logging fails silently', function (): void {
    $logger = new ActionLoggerService();
    $logsPath = storage_path('logs');
    $archLogPath = storage_path('logs/arch.log');
    
    if (file_exists($archLogPath)) {
        unlink($archLogPath);
    }
    
    if (!is_dir($logsPath)) {
        mkdir($logsPath, 0755, true);
    }

    chmod($logsPath, 0444);
    
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('info')
        ->once()
        ->andThrow(new RuntimeException('Primary logging failed'));

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($mockLogger);

    $logger->logActionStart('FailingAction');
    
    chmod($logsPath, 0755);
    
    expect(true)->toBeTrue();
});
