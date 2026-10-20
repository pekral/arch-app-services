<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpExceptionsInActionsRule;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Pekral\Arch\Action\ArchAction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

// Intentionally throws HTTP exceptions to trigger NoHttpExceptionsInActionsRule.
final readonly class ThrowIfHttpExceptionAction implements ArchAction
{

    public function __invoke(bool $condition): void
    {
        throw_if($condition, NotFoundHttpException::class, 'Not found.');
    }

}

final readonly class NewHttpExceptionAction implements ArchAction
{

    public function __invoke(): never
    {
        throw new NotFoundHttpException('Not found.');
    }

}

final readonly class AbortIfAction implements ArchAction
{

    public function __invoke(bool $condition): void
    {
        abort_if($condition, 404);
    }

}

final readonly class AbortUnlessAction implements ArchAction
{

    public function __invoke(bool $condition): void
    {
        abort_unless($condition, 404);
    }

}

final readonly class AbortAction implements ArchAction
{

    public function __invoke(): void
    {
        abort(404);
    }

}

final readonly class HttpResponseExceptionAction implements ArchAction
{

    public function __invoke(bool $condition): void
    {
        throw_if($condition, HttpResponseException::class);
    }

}

final readonly class ThrowUnlessHttpExceptionInstanceAction implements ArchAction
{

    public function __invoke(bool $condition): void
    {
        throw_unless($condition, new NotFoundHttpException('Not found.'));
    }

}

final readonly class ThrownHttpExceptionVariableAction implements ArchAction
{

    public function __invoke(): never
    {
        $exception = new NotFoundHttpException('Not found.');

        throw $exception;
    }

}

// Valid: a domain exception is the sanctioned way to signal "not found".
final readonly class DomainExceptionAction implements ArchAction
{

    public function __invoke(bool $condition): never
    {
        throw_if($condition, ModelNotFoundException::class);

        throw new ModelNotFoundException();
    }

}

// Valid: no HTTP exception can be resolved from any of these throws.
final readonly class NonHttpThrowAction implements ArchAction
{

    public function __invoke(bool $condition, Throwable $exception): never
    {
        throw_if($condition);

        throw_if($condition, 'Pekral\Arch\Tests\Fixtures\PHPStan\Missing\NotAnExceptionAtAll');

        throw $exception;
    }

}
