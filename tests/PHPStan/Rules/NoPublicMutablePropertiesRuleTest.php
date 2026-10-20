<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoPublicMutablePropertiesRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_PUBLIC_MUTABLE_PROPERTIES_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoPublicMutablePropertiesRule';

function publicMutablePropertyMessage(string $class, string $property): string
{
    return sprintf(
        'Public property Pekral\Arch\Tests\Fixtures\PHPStan\NoPublicMutablePropertiesRule\%s::$%s can be modified after construction.'
        . ' Architecture classes must not expose mutable state: make it readonly, restrict its setter with private(set), or make it non-public.',
        $class,
        $property,
    );
}

test(
    'NoPublicMutablePropertiesRule reports a public mutable property in an architecture layer',
    function (string $fixture, string $class, string $property): void {
        $errors = PhpstanFixtureRunner::run(NO_PUBLIC_MUTABLE_PROPERTIES_FIXTURES);
    
        expect($errors[$fixture] ?? [])->toContain(publicMutablePropertyMessage($class, $property));
    },
)->with([
    'Action' => ['PublicMutableAction.php', 'PublicMutableAction', 'discount'],
    'Model Manager promoted property' => ['PublicMutableModelManager.php', 'PublicMutableModelManager', 'connectionName'],
    'Repository' => ['PublicMutableRepository.php', 'PublicMutableRepository', 'defaultLimit'],
]);

test('NoPublicMutablePropertiesRule reports under a dedicated identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_PUBLIC_MUTABLE_PROPERTIES_FIXTURES);

    expect($identifiers['PublicMutableRepository.php'] ?? [])->toBe([NoPublicMutablePropertiesRule::IDENTIFIER]);
});

test('NoPublicMutablePropertiesRule accepts encapsulated state, data classes and configured exclusions', function (string $fixture): void {
    $errors = PhpstanFixtureRunner::run(NO_PUBLIC_MUTABLE_PROPERTIES_FIXTURES);

    expect($errors)->not->toHaveKey($fixture);
})->with([
    'class implementing a configured excluded interface' => 'ExcludedByInterfaceModelManager.php',
    'class in a configured excluded namespace' => 'ExcludedNamespaceModelManager.php',
    'DTO public properties' => 'PublicPropertiesDto.php',
    'Eloquent model public properties' => 'PublicPropertiesModel.php',
    'public readonly, private(set) and private mutable state' => 'EncapsulatedModelManager.php',
    'public readonly property in a readonly Service' => 'ReadonlyPublicModelService.php',
]);

test('NoPublicMutablePropertiesRule checks every architecture class when no exclusion is configured', function (string $fixture, string $class): void {
    $errors = PhpstanFixtureRunner::runWithDefaultArguments(NO_PUBLIC_MUTABLE_PROPERTIES_FIXTURES);

    expect($errors[$fixture] ?? [])->toBe([publicMutablePropertyMessage($class, 'retries')]);
})->with([
    'excluded interface' => ['ExcludedByInterfaceModelManager.php', 'ExcludedByInterfaceModelManager'],
    'excluded namespace' => ['ExcludedNamespaceModelManager.php', 'Excluded\ExcludedNamespaceModelManager'],
]);
