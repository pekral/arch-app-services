<?php

declare(strict_types = 1);

namespace Pekral\Arch\Action;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

use function config;
use function date;
use function file_put_contents;
use function json_encode;
use function storage_path;

/**
 * Trait for logging action execution states.
 * Provides methods to log action start, success, and failure events.
 * Uses configurable logging channel from arch.action_logging.channel.
 * Falls back to arch.log file if primary logging fails.
 */
trait ActionLogger
{

    /**
     * Log that an action has started.
     *
     * @param array<string, mixed> $context
     */
    public function logActionStart(string $action, array $context = []): void
    {
        if (!$this->isActionLoggingEnabled()) {
            return;
        }

        $this->safeLog('info', 'Action started: ' . $action, $context, $action, 'start');
    }

    /**
     * Log that an action has completed successfully.
     *
     * @param array<string, mixed> $context
     */
    public function logActionSuccess(string $action, array $context = []): void
    {
        if (!$this->isActionLoggingEnabled()) {
            return;
        }

        $this->safeLog('info', 'Action completed: ' . $action, $context, $action, 'success');
    }

    /**
     * Log that an action has failed.
     *
     * @param array<string, mixed> $context
     */
    public function logActionFailure(string $action, string $error, array $context = []): void
    {
        if (!$this->isActionLoggingEnabled()) {
            return;
        }

        $this->safeLog('error', sprintf('Action failed: %s - %s', $action, $error), $context, $action, 'failure');
    }

    /**
     * Safely log with fallback to arch.log file.
     *
     * @param array<string, mixed> $context
     */
    private function safeLog(string $level, string $message, array $context, string $action, string $type): void
    {
        try {
            $this->getLogger()->{$level}($message, $context);
        } catch (Throwable $exception) {
            $this->fallbackLog($level, $message, $context, $action, $type, $exception);
        }
    }

    /**
     * Fallback logging to arch.log file when primary logging fails.
     *
     * @param array<string, mixed> $context
     */
    private function fallbackLog(string $level, string $message, array $context, string $action, string $type, Throwable $loggingException): void
    {
        $fallbackMessage = $this->createFallbackMessage($level, $message, $context, $action, $type, $loggingException);

        try {
            file_put_contents(
                storage_path('logs/arch.log'),
                $fallbackMessage,
                FILE_APPEND | LOCK_EX,
            );
        } catch (Throwable) {
            // At this point we can't do much more, but we won't throw
            // to prevent breaking the application
        }
    }

    /**
     * Create formatted fallback message.
     *
     * @param array<string, mixed> $context
     */
    private function createFallbackMessage(
        string $level,
        string $message,
        array $context,
        string $action,
        string $type,
        Throwable $loggingException,
    ): string {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return sprintf(
            <<<'TEMPLATE'
[%s] ARCH FALLBACK LOG
Level: %s
Action: %s
Type: %s
Original Message: %s
Context: %s
Logging Error: %s
Logging Error File: %s:%d
Stack Trace: %s
%s

TEMPLATE,
            $timestamp,
            strtoupper($level),
            $action,
            $type,
            $message,
            $contextJson ?: '{}',
            $loggingException->getMessage(),
            $loggingException->getFile(),
            $loggingException->getLine(),
            $loggingException->getTraceAsString(),
            str_repeat('-', 80),
        );
    }

    /**
     * Get the configured logger channel.
     */
    private function getLogger(): LoggerInterface
    {
        $channel = config('arch.action_logging.channel', 'stack');
        assert(is_string($channel));
        
        return Log::channel($channel);
    }

    /**
     * Check if action logging is enabled.
     */
    private function isActionLoggingEnabled(): bool
    {
        return (bool) config('arch.action_logging.enabled', true);
    }

}
