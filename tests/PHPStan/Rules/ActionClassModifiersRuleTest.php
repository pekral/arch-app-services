<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\ActionClassModifiersRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const ACTION_CLASS_MODIFIERS_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/ActionClassModifiersRule';

function actionModifierMessage(string $actionClass, string $modifier): string
{
    return sprintf('Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionClassModifiersRule\%s" must be declared as "%s".', $actionClass, $modifier);
}

test('ActionClassModifiersRule reports a concrete Action that is not final or not readonly', function (): void {
    $errors = PhpstanFixtureRunner::run(ACTION_CLASS_MODIFIERS_FIXTURES);

    expect($errors['NotFinalAction.php'] ?? [])->toBe([actionModifierMessage('NotFinalAction', 'final')])
        ->and($errors['NotReadonlyAction.php'] ?? [])->toBe([actionModifierMessage('NotReadonlyAction', 'readonly')])
        ->and($errors['NeitherFinalNorReadonlyAction.php'] ?? [])->toBe([
            actionModifierMessage('NeitherFinalNorReadonlyAction', 'final'),
            actionModifierMessage('NeitherFinalNorReadonlyAction', 'readonly'),
        ]);
});

test('ActionClassModifiersRule reports violations under dedicated identifiers', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(ACTION_CLASS_MODIFIERS_FIXTURES);

    expect($identifiers['NeitherFinalNorReadonlyAction.php'] ?? [])->toBe([
        ActionClassModifiersRule::NOT_FINAL_IDENTIFIER,
        ActionClassModifiersRule::NOT_READONLY_IDENTIFIER,
    ]);
});

test('ActionClassModifiersRule accepts final readonly Actions and ignores non-concrete Action types', function (string $fixture): void {
    $errors = PhpstanFixtureRunner::run(ACTION_CLASS_MODIFIERS_FIXTURES);

    expect($errors)->not->toHaveKey($fixture);
})->with([
    'abstract Action support class' => 'AbstractActionSupport.php',
    'anonymous Action class' => 'AnonymousActionFactory.php',
    'final readonly Action' => 'ValidFinalReadonlyAction.php',
    'interface extending ArchAction' => 'ActionContract.php',
]);
