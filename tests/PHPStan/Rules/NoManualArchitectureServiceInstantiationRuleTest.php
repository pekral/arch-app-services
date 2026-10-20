<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoManualArchitectureServiceInstantiationRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_MANUAL_ARCHITECTURE_INSTANTIATION_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoManualArchitectureServiceInstantiationRule';

function manualArchitectureInstantiationMessage(string $class): string
{
    return sprintf(
        'Architecture class %s must not be instantiated with "new". Inject it through the constructor'
        . ' so the container resolves it and its dependencies.',
        $class,
    );
}

test('NoManualArchitectureServiceInstantiationRule reports new on every architecture layer', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_MANUAL_ARCHITECTURE_INSTANTIATION_FIXTURES);

    expect($errors['ManualInstantiationController.php'] ?? [])->toBe([
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Services\User\UserRepository'),
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Services\User\UserModelManager'),
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Services\User\UserModelManager'),
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Services\User\UserModelService'),
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Services\User\UserRepository'),
        manualArchitectureInstantiationMessage('Pekral\Arch\Examples\Actions\User\GetUser'),
    ]);
});

test('NoManualArchitectureServiceInstantiationRule reports under a dedicated identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_MANUAL_ARCHITECTURE_INSTANTIATION_FIXTURES);

    expect(array_unique($identifiers['ManualInstantiationController.php'] ?? []))
        ->toBe([NoManualArchitectureServiceInstantiationRule::IDENTIFIER]);
});

test('NoManualArchitectureServiceInstantiationRule accepts constructor injection and ordinary object creation', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_MANUAL_ARCHITECTURE_INSTANTIATION_FIXTURES);

    expect($errors)->not->toHaveKey('InjectedDependencies.php');
});
