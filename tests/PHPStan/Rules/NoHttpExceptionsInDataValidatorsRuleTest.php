<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInDataValidatorsRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_HTTP_EXCEPTIONS_IN_DATA_VALIDATORS_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpExceptionsInDataValidatorsRule';

function noHttpExceptionInDataValidatorMessage(string $violation): string
{
    return sprintf(
        'A Data Validator must not produce an HTTP response exception (%s). Throw a domain exception such as'
        . ' Illuminate\Database\Eloquent\ModelNotFoundException instead and let the entry point translate it into an HTTP response.',
        $violation,
    );
}

test('NoHttpExceptionsInDataValidatorsRule reports HTTP exceptions thrown from a Data Validator', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_DATA_VALIDATORS_FIXTURES);

    // The ValidationException validator in the same fixture adds no message.
    expect($errors['DataValidators.php'] ?? [])->toBe([
        noHttpExceptionInDataValidatorMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
        noHttpExceptionInDataValidatorMessage('Symfony\Component\HttpKernel\Exception\NotFoundHttpException'),
    ]);
});

test('NoHttpExceptionsInDataValidatorsRule ignores a Data Validator outside the configured namespaces', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_DATA_VALIDATORS_FIXTURES);

    expect($errors)->not->toHaveKey('OutsideNamespaceDataValidator.php');
});

test('NoHttpExceptionsInDataValidatorsRule ignores a class that is not a Data Validator', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_HTTP_EXCEPTIONS_IN_DATA_VALIDATORS_FIXTURES);

    expect($errors)->not->toHaveKey('NotADataValidatorService.php');
});

test('NoHttpExceptionsInDataValidatorsRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_HTTP_EXCEPTIONS_IN_DATA_VALIDATORS_FIXTURES);

    expect($identifiers['DataValidators.php'] ?? [])
        ->toBe(array_fill(0, 2, NoHttpExceptionsInDataValidatorsRule::IDENTIFIER));
});
