<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoMutableStaticStateRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_MUTABLE_STATIC_STATE_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoMutableStaticStateRule';

function mutableStaticStateMessage(string $class, string $property): string
{
    return sprintf(
        'Static property Pekral\Arch\Tests\Fixtures\PHPStan\NoMutableStaticStateRule\%s::$%s holds mutable state shared across'
        . ' instances, requests and queue jobs in long-running workers. Use a class constant, an injected dependency or a cache instead.',
        $class,
        $property,
    );
}

test('NoMutableStaticStateRule reports a static property in an architecture layer', function (string $fixture, string $class, string $property): void {
    $errors = PhpstanFixtureRunner::run(NO_MUTABLE_STATIC_STATE_FIXTURES);

    expect($errors[$fixture] ?? [])->toContain(mutableStaticStateMessage($class, $property));
})->with([
    'Action' => ['StaticStateAction.php', 'StaticStateAction', 'processedOrders'],
    'Model Manager' => ['StaticStateModelManager.php', 'StaticStateModelManager', 'lastCreatedUser'],
    'Repository' => ['StaticStateRepository.php', 'StaticStateRepository', 'queryCount'],
]);

test('NoMutableStaticStateRule reports under a dedicated identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_MUTABLE_STATIC_STATE_FIXTURES);

    expect($identifiers['StaticStateModelManager.php'] ?? [])->toBe([NoMutableStaticStateRule::IDENTIFIER]);
});

test('NoMutableStaticStateRule accepts constants, static methods and classes outside the architecture', function (string $fixture): void {
    $errors = PhpstanFixtureRunner::run(NO_MUTABLE_STATIC_STATE_FIXTURES);

    expect($errors)->not->toHaveKey($fixture);
})->with([
    'class outside the architecture layers' => 'NotAnArchitectureClass.php',
    'constant, public static method and instance state in a Model Manager' => 'StatelessModelManager.php',
    'constant and private static method in an Action' => 'StatelessAction.php',
]);
