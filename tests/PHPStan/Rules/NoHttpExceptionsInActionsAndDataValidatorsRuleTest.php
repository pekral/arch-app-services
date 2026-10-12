<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInActionsAndDataValidatorsRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_HTTP_EXCEPTIONS_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpExceptionsInActionsAndDataValidatorsRule';

function noHttpExceptionMessage(string $layer, string $violation): string
{
    return sprintf(
        '%s must not produce an HTTP response exception (%s). Throw a domain exception such as'
        . ' Illuminate\Database\Eloquent\ModelNotFoundException instead and let the entry point translate it into an HTTP response.',
        $layer,
        $violation,
    );
}

test('NoHttpExceptionsInActionsAndDataValidatorsRule reports HTTP exceptions thrown from an ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_FIXTURES);

    // Valid DomainExceptionAction and NonHttpThrowAction in the same fixture add no message.
    expect($errors['ArchActionsThrowingHttpExceptions.php'] ?? [])->toBe([
        noHttpExceptionMessage('An Action', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionMessage('An Action', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionMessage('An Action', 'abort_if()'),
        noHttpExceptionMessage('An Action', 'abort_unless()'),
        noHttpExceptionMessage('An Action', 'abort()'),
        noHttpExceptionMessage('An Action', 'Illuminate\Http\Exceptions\HttpResponseException'),
        noHttpExceptionMessage('An Action', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionMessage('An Action', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ]);
});

test('NoHttpExceptionsInActionsAndDataValidatorsRule treats a class in a configured Action namespace as an Action', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_FIXTURES);

    expect($errors['ConfiguredNamespaceActions.php'] ?? [])->toBe([
        noHttpExceptionMessage('An Action', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ]);
});

test('NoHttpExceptionsInActionsAndDataValidatorsRule reports HTTP exceptions thrown from a Data Validator only', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_FIXTURES);

    // The ValidationException validator and the plain service in the fixtures add no message.
    expect($errors['DataValidators.php'] ?? [])->toBe([
        noHttpExceptionMessage('A Data Validator', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionMessage('A Data Validator', 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ])
        ->and($errors)->not->toHaveKey('NotAnActionNorValidatorService.php');
});

test('NoHttpExceptionsInActionsAndDataValidatorsRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_HTTP_EXCEPTIONS_FIXTURES);

    expect($identifiers['ArchActionsThrowingHttpExceptions.php'] ?? [])
        ->toBe(array_fill(0, 8, NoHttpExceptionsInActionsAndDataValidatorsRule::ACTION_IDENTIFIER))
        ->and($identifiers['ConfiguredNamespaceActions.php'] ?? [])
        ->toBe([NoHttpExceptionsInActionsAndDataValidatorsRule::ACTION_IDENTIFIER])
        ->and($identifiers['DataValidators.php'] ?? [])
        ->toBe(array_fill(0, 2, NoHttpExceptionsInActionsAndDataValidatorsRule::DATA_VALIDATOR_IDENTIFIER));
});
