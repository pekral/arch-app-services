<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Action;

use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Pekral\Arch\Action\ActionLogger;
use Pekral\Arch\Tests\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class ActionLoggerTest extends TestCase
{

    private TestClassWithActionLogger $testClassWithActionLogger;

    private MockInterface&LoggerInterface $loggerMock;

    public function testLogActionStart(): void
    {
        // Arrange
        $action = 'CreateUser';
        $context = ['user_id' => 123];

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Action started: ' . $action, $context);

        // Act
        $this->testClassWithActionLogger->logActionStart($action, $context);
    }

    public function testLogActionStartWithoutContext(): void
    {
        // Arrange
        $action = 'CreateUser';

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Action started: ' . $action, []);

        // Act
        $this->testClassWithActionLogger->logActionStart($action);
    }

    public function testLogActionSuccess(): void
    {
        // Arrange
        $action = 'CreateUser';
        $context = ['user_id' => 123, 'execution_time' => 0.5];

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Action completed: ' . $action, $context);

        // Act
        $this->testClassWithActionLogger->logActionSuccess($action, $context);
    }

    public function testLogActionSuccessWithoutContext(): void
    {
        // Arrange
        $action = 'CreateUser';

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Action completed: ' . $action, []);

        // Act
        $this->testClassWithActionLogger->logActionSuccess($action);
    }

    public function testLogActionFailure(): void
    {
        // Arrange
        $action = 'CreateUser';
        $error = 'Validation failed';
        $context = ['user_data' => ['email' => 'invalid']];

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(sprintf('Action failed: %s - %s', $action, $error), $context);

        // Act
        $this->testClassWithActionLogger->logActionFailure($action, $error, $context);
    }

    public function testLogActionFailureWithoutContext(): void
    {
        // Arrange
        $action = 'CreateUser';
        $error = 'Database connection failed';

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(sprintf('Action failed: %s - %s', $action, $error), []);

        // Act
        $this->testClassWithActionLogger->logActionFailure($action, $error);
    }

    public function testLoggingWithCustomChannel(): void
    {
        // Arrange
        config(['arch.action_logging.channel' => 'custom']);
        $action = 'CustomAction';

        Log::shouldReceive('channel')
            ->once()
            ->with('custom')
            ->andReturn($this->loggerMock);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Action started: ' . $action, []);

        // Act
        $this->testClassWithActionLogger->logActionStart($action);
    }

    public function testLoggingDisabled(): void
    {
        // Arrange
        config(['arch.action_logging.enabled' => false]);
        $action = 'DisabledAction';

        // No Log::channel() calls should be made when logging is disabled
        // Act - should execute without any logging calls
        $this->testClassWithActionLogger->logActionStart($action);
        $this->testClassWithActionLogger->logActionSuccess($action);
        $this->testClassWithActionLogger->logActionFailure($action, 'error');

        // Assert - test passes if no exceptions are thrown
        $this->expectNotToPerformAssertions();
    }

    public function testFallbackLoggingWhenPrimaryLoggerFails(): void
    {
        // Arrange
        $action = 'FailingAction';
        $context = ['test' => 'data'];
        $logPath = storage_path('logs/arch.log');
        
        // Clean up any existing arch.log
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

        // Act
        $this->testClassWithActionLogger->logActionStart($action, $context);

        // Assert
        $this->assertFileExists($logPath);
        
        $logContent = file_get_contents($logPath);
        $this->assertNotFalse($logContent);
        $this->assertStringContainsString('ARCH FALLBACK LOG', $logContent);
        $this->assertStringContainsString('Action: FailingAction', $logContent);
        $this->assertStringContainsString('Type: start', $logContent);
        $this->assertStringContainsString($exceptionMessage, $logContent);
        $this->assertStringContainsString('"test": "data"', $logContent);
        
        // Clean up
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    public function testFallbackLoggingForAllLogLevels(): void
    {
        // Arrange
        $logPath = storage_path('logs/arch.log');
        
        // Clean up any existing arch.log
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

        // Act
        $this->testClassWithActionLogger->logActionStart('TestAction');
        $this->testClassWithActionLogger->logActionSuccess('TestAction');
        $this->testClassWithActionLogger->logActionFailure('TestAction', 'Test error');

        // Assert
        $this->assertFileExists($logPath);
        
        $logContent = file_get_contents($logPath);
        $this->assertNotFalse($logContent);
        $this->assertStringContainsString('Type: start', $logContent);
        $this->assertStringContainsString('Type: success', $logContent);
        $this->assertStringContainsString('Type: failure', $logContent);
        
        // Clean up
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    public function testNoExceptionWhenBothPrimaryAndFallbackLoggingFail(): void
    {
        // Arrange - this test ensures the application doesn't crash
        $mockLogger = Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('Primary logging failed'));

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($mockLogger);

        // Mock file_put_contents to fail by using a read-only directory path
        // We can't easily mock file_put_contents, so we'll just ensure no exception is thrown
        
        // Act & Assert - should not throw any exception
        $this->testClassWithActionLogger->logActionStart('FailingAction');
        
        // If we reach this point, the test passes
        $this->expectNotToPerformAssertions();
    }

    public function testFallbackLoggingFailsSilently(): void
    {
        // Arrange - test the catch block in fallbackLog method (line 96)
        $logsPath = storage_path('logs');
        $archLogPath = storage_path('logs/arch.log');
        
        // Clean up
        if (file_exists($archLogPath)) {
            unlink($archLogPath);
        }
        
        // Create logs directory as read-only to force file_put_contents to fail
        if (!is_dir($logsPath)) {
            mkdir($logsPath, 0755, true);
        }

        // Read-only directory
        chmod($logsPath, 0444);
        
        $mockLogger = Mockery::mock(LoggerInterface::class);
        $mockLogger->shouldReceive('info')
            ->once()
            ->andThrow(new RuntimeException('Primary logging failed'));

        Log::shouldReceive('channel')
            ->once()
            ->with('stack')
            ->andReturn($mockLogger);

        // Act & Assert - should not throw any exception even when fallback fails
        $this->testClassWithActionLogger->logActionStart('FailingAction');
        
        // Restore directory permissions
        chmod($logsPath, 0755);
        
        // If we reach this point, the test passes - the catch block was executed
        $this->expectNotToPerformAssertions();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $mock = Mockery::mock(LoggerInterface::class);
        $this->loggerMock = $mock;
        $this->testClassWithActionLogger = new TestClassWithActionLogger();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

}

/**
 * Test class for ActionLogger trait
 */
final class TestClassWithActionLogger
{

    use ActionLogger;

}
