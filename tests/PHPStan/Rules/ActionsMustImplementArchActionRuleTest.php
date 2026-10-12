<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\ActionsMustImplementArchActionRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const ACTIONS_MUST_IMPLEMENT_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/ActionsMustImplementArchActionRule';

test('ActionsMustImplementArchActionRule reports a class in a configured Actions namespace that does not implement ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(ACTIONS_MUST_IMPLEMENT_FIXTURES);

    $expectedMessage = 'Class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule\Actions\MissingInterfaceAction"'
        . ' in the "Pekral\Arch\Tests\Fixtures\PHPStan\ActionsMustImplementArchActionRule\Actions\" namespace'
        . ' must implement Pekral\Arch\Action\ArchAction.';

    expect($errors['MissingInterfaceAction.php'] ?? [])->toBe([$expectedMessage]);
});

test('ActionsMustImplementArchActionRule reports the violation with the identifier used to ignore it', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(ACTIONS_MUST_IMPLEMENT_FIXTURES);

    expect($identifiers['MissingInterfaceAction.php'] ?? [])->toBe([ActionsMustImplementArchActionRule::IDENTIFIER]);
});

test(
    'ActionsMustImplementArchActionRule ignores an ArchAction, an anonymous class, a non-class declaration and a class outside the configured namespace',
    function (): void {
        $errors = PhpstanFixtureRunner::run(ACTIONS_MUST_IMPLEMENT_FIXTURES);
    
        expect($errors)->not->toHaveKey('ValidArchAction.php')
            ->and($errors)->not->toHaveKey('ValidNonClassDeclarations.php')
            ->and($errors)->not->toHaveKey('OutsideConfiguredNamespaceService.php');
    },
);
