<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Illuminate\Support\Facades\Log;
use Mockery;
use Pekral\Arch\Action\ActionLogger;
use Psr\Log\LoggerInterface;
use RuntimeException;

beforeEach(function (): void {
    $this->logger = new TestClassWithActionLogger();
});

test('log action start writes info log', function (): void {
    Log::spy();
    $action = 'CreateUser';
    $context = ['user_id' => 123];

    $this->logger->logActionStart($action, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action start works without context', function (): void {
    Log::spy();
    $action = 'CreateUser';

    $this->logger->logActionStart($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action success writes info log', function (): void {
    Log::spy();
    $action = 'CreateUser';
    $context = ['user_id' => 123, 'execution_time' => 0.5];

    $this->logger->logActionSuccess($action, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action success works without context', function (): void {
    Log::spy();
    $action = 'CreateUser';

    $this->logger->logActionSuccess($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action failure writes error log', function (): void {
    Log::spy();
    $action = 'CreateUser';
    $error = 'Validation failed';
    $context = ['user_data' => ['email' => 'invalid']];

    $this->logger->logActionFailure($action, $error, $context);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('log action failure works without context', function (): void {
    Log::spy();
    $action = 'CreateUser';
    $error = 'Database connection failed';

    $this->logger->logActionFailure($action, $error);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('stack');
});

test('logging uses custom channel', function (): void {
    config(['arch.action_logging.channel' => 'custom']);
    Log::spy();
    $action = 'CustomAction';

    $this->logger->logActionStart($action);

    Log::shouldHaveReceived('channel')
        ->once()
        ->with('custom');
});

test('logging disabled skips all logging', function (): void {
    config(['arch.action_logging.enabled' => false]);
    Log::spy();
    $action = 'DisabledAction';

    $this->logger->logActionStart($action);
    $this->logger->logActionSuccess($action);
    $this->logger->logActionFailure($action, 'error');

    Log::shouldNotHaveReceived('channel');
});

test('fallback logging when primary logger fails', function (): void {
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

    $this->logger->logActionStart($action, $context);

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

    $this->logger->logActionStart('TestAction');
    $this->logger->logActionSuccess('TestAction');
    $this->logger->logActionFailure('TestAction', 'Test error');

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
    $mockLogger = Mockery::mock(LoggerInterface::class);
    $mockLogger->shouldReceive('info')
        ->once()
        ->andThrow(new RuntimeException('Primary logging failed'));

    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturn($mockLogger);

    $this->logger->logActionStart('FailingAction');

    expect(true)->toBeTrue();
});

test('fallback logging fails silently', function (): void {
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

    $this->logger->logActionStart('FailingAction');
    
    chmod($logsPath, 0755);
    
    expect(true)->toBeTrue();
});

final class TestClassWithActionLogger
{

    use ActionLogger;

}
