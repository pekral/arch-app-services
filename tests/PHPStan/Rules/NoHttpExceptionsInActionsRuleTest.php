<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInActionsRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_HTTP_EXCEPTIONS_IN_ACTIONS_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpExceptionsInActionsRule';

function noHttpExceptionInActionMessage(string $violation): string
{
    return sprintf(
        'An Action must not produce an HTTP response exception (%s). Throw a domain exception such as'
        . ' Illuminate\Database\Eloquent\ModelNotFoundException instead and let the entry point translate it into an HTTP response.',
        $violation,
    );
}

test('NoHttpExceptionsInActionsRule reports HTTP exceptions thrown from an ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_ACTIONS_FIXTURES);

    // Valid DomainExceptionAction and NonHttpThrowAction in the same fixture add no message.
    expect($errors['ArchActionsThrowingHttpExceptions.php'] ?? [])->toBe([
        noHttpExceptionInActionMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionInActionMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionInActionMessage('abort_if()'),
        noHttpExceptionInActionMessage('abort_unless()'),
        noHttpExceptionInActionMessage('abort()'),
        noHttpExceptionInActionMessage('Illuminate\Http\Exceptions\HttpResponseException'),
        noHttpExceptionInActionMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionInActionMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ]);
});

test('NoHttpExceptionsInActionsRule treats a class in a configured Action namespace as an Action', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_ACTIONS_FIXTURES);

    expect($errors['ConfiguredNamespaceActions.php'] ?? [])->toBe([
        noHttpExceptionInActionMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ]);
});

test('NoHttpExceptionsInActionsRule ignores a class that is not an Action', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_ACTIONS_FIXTURES);

    expect($errors)->not->toHaveKey('NotAnActionService.php');
});

test('NoHttpExceptionsInActionsRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_HTTP_EXCEPTIONS_IN_ACTIONS_FIXTURES);

    expect($identifiers['ArchActionsThrowingHttpExceptions.php'] ?? [])
        ->toBe(array_fill(0, 8, NoHttpExceptionsInActionsRule::IDENTIFIER))
        ->and($identifiers['ConfiguredNamespaceActions.php'] ?? [])
        ->toBe([NoHttpExceptionsInActionsRule::IDENTIFIER]);
});
