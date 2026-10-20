<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\ImmutableDependenciesRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const IMMUTABLE_DEPENDENCIES_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/ImmutableDependenciesRule';

function mutableDependencyMessage(string $class): string
{
    return sprintf(
        'Dependency property Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule\%s::$paymentGateway must be readonly.'
        . ' Inject it through the constructor and declare it readonly (or make the class readonly) so it cannot be replaced after construction.',
        $class,
    );
}

function dependencyAssignedOutsideConstructorMessage(string $class, string $method): string
{
    return sprintf(
        'Dependency property Pekral\Arch\Tests\Fixtures\PHPStan\ImmutableDependenciesRule\%s::$paymentGateway is assigned in %s().'
        . ' Dependencies must be injected through the constructor and never replaced (no setter or property injection).',
        $class,
        $method,
    );
}

test(
    'ImmutableDependenciesRule reports a dependency stored in a mutable property in every architecture layer',
    function (string $fixture, string $class): void {
        $errors = PhpstanFixtureRunner::run(IMMUTABLE_DEPENDENCIES_FIXTURES);
    
        expect($errors[$fixture] ?? [])->toContain(mutableDependencyMessage($class));
    },
)->with([
    'Action' => ['PublicMutableDependencyAction.php', 'PublicMutableDependencyAction'],
    'Model Manager' => ['MutableDependencyModelManager.php', 'MutableDependencyModelManager'],
    'Repository' => ['PublicDependencyRepository.php', 'PublicDependencyRepository'],
]);

test(
    'ImmutableDependenciesRule reports setter injection and reassignment of a dependency',
    function (string $fixture, string $class, string $method): void {
        $errors = PhpstanFixtureRunner::run(IMMUTABLE_DEPENDENCIES_FIXTURES);
    
        expect($errors[$fixture] ?? [])->toContain(dependencyAssignedOutsideConstructorMessage($class, $method));
    },
)->with([
    'reassigned Action dependency' => ['ReassignedDependencyAction.php', 'ReassignedDependencyAction', 'replacePaymentGateway'],
    'setter-injected Action' => ['SetterInjectedAction.php', 'SetterInjectedAction', 'setPaymentGateway'],
    'setter-injected Service' => ['SetterInjectedModelService.php', 'SetterInjectedModelService', 'initializePaymentGateway'],
]);

test('ImmutableDependenciesRule reports violations under dedicated identifiers', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(IMMUTABLE_DEPENDENCIES_FIXTURES);

    expect($identifiers['SetterInjectedAction.php'] ?? [])
        ->toContain(ImmutableDependenciesRule::MUTABLE_DEPENDENCY_IDENTIFIER)
        ->toContain(ImmutableDependenciesRule::DEPENDENCY_ASSIGNED_OUTSIDE_CONSTRUCTOR_IDENTIFIER);
});

test('ImmutableDependenciesRule accepts immutable dependencies, data properties and classes outside the architecture', function (string $fixture): void {
    $errors = PhpstanFixtureRunner::run(IMMUTABLE_DEPENDENCIES_FIXTURES);

    expect($errors)->not->toHaveKey($fixture);
})->with([
    'class outside the architecture layers' => 'NotAnArchitectureClass.php',
    'constructor-injected dependency in a readonly Action' => 'ValidReadonlyAction.php',
    'method-local object creation' => 'LocalObjectCreationAction.php',
    'readonly promoted dependency and a mutable Eloquent model property in a non-readonly class' => 'ReadonlyPropertyModelManager.php',
]);
