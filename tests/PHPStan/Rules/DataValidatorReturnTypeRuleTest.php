<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\DataValidatorReturnTypeRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const DATA_VALIDATOR_RETURN_TYPE_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/DataValidatorReturnTypeRule';

function dataValidatorReturnTypeMessage(string $method): string
{
    return sprintf('Public method "%s()" of a Data Validator must declare the return type bool or void.', $method);
}

test('DataValidatorReturnTypeRule reports public Data Validator methods without a bool or void return type', function (): void {
    $errors = PhpstanFixtureRunner::run(DATA_VALIDATOR_RETURN_TYPE_FIXTURES);

    // OrderDataValidator (bool, void, constructor, non-public helpers) adds no message.
    expect($errors['DataValidators.php'] ?? [])->toBe(array_map(
        dataValidatorReturnTypeMessage(...),
        [
            'Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators\InvoiceDataValidator::validate',
            'Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators\InvoiceDataValidator::isPaid',
            'Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators\InvoiceDataValidator::isDue',
            'Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators\InvoiceDataValidator::check',
            'Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Validators\InvoiceDataValidator::make',
        ],
    ));
});

test('DataValidatorReturnTypeRule ignores a Data Validator outside the configured namespaces', function (): void {
    $errors = PhpstanFixtureRunner::run(DATA_VALIDATOR_RETURN_TYPE_FIXTURES);

    expect($errors)->not->toHaveKey('OutsideNamespaceDataValidator.php');
});

test('DataValidatorReturnTypeRule inspects a Data Validator in every namespace without dataValidatorNamespaces', function (): void {
    $errors = PhpstanFixtureRunner::runWithDefaultArguments(DATA_VALIDATOR_RETURN_TYPE_FIXTURES);

    expect($errors['OutsideNamespaceDataValidator.php'] ?? [])->toBe([
        dataValidatorReturnTypeMessage('Pekral\Arch\Tests\Fixtures\PHPStan\DataValidatorReturnTypeRule\Elsewhere\OutsideNamespaceDataValidator::errors'),
    ])
        ->and($errors['DataValidators.php'] ?? [])->toHaveCount(5)
        ->and($errors)->not->toHaveKey('NotADataValidatorService.php');
});

test('DataValidatorReturnTypeRule ignores a class that is not a Data Validator', function (): void {
    $errors = PhpstanFixtureRunner::run(DATA_VALIDATOR_RETURN_TYPE_FIXTURES);

    expect($errors)->not->toHaveKey('NotADataValidatorService.php');
});

test('DataValidatorReturnTypeRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(DATA_VALIDATOR_RETURN_TYPE_FIXTURES);

    expect($identifiers['DataValidators.php'] ?? [])
        ->toBe(array_fill(0, 5, DataValidatorReturnTypeRule::IDENTIFIER));
});
