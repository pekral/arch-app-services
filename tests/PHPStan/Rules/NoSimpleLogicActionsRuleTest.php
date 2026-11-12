<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoSimpleLogicActionsRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const NO_SIMPLE_LOGIC_ACTIONS_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/NoSimpleLogicActionsRule';

const NO_SIMPLE_LOGIC_ACTIONS_NAMESPACE = 'Pekral\Arch\Tests\Fixtures\PHPStan\NoSimpleLogicActionsRule\\';

function simpleLogicActionMessage(string $className): string
{
    return sprintf(
        'Action "%s" holds only simple logic (no branching, transaction, validation, authorization or second collaborator).'
        . ' Inline it at its call site; when several call sites reuse it, ignore this error by its identifier.',
        NO_SIMPLE_LOGIC_ACTIONS_NAMESPACE . $className,
    );
}

test('NoSimpleLogicActionsRule reports an Action that holds only one collaborator call without branching', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors['SimpleActions.php'] ?? [])->toBe(array_map(
        simpleLogicActionMessage(...),
        ['DispatchAccountsAction', 'ForeachDispatchAction', 'ForwardingAction', 'PrivateHelperAction', 'NoCollaboratorAction'],
    ));
});

test('NoSimpleLogicActionsRule treats a class in a configured Action namespace as an Action', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors['ConfiguredNamespaceActions.php'] ?? [])->toBe([simpleLogicActionMessage('Legacy\LegacyForwardingAction')]);
});

test('NoSimpleLogicActionsRule ignores an Action that branches or runs in a transaction', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors)->not->toHaveKey('LogicActions.php');
});

test('NoSimpleLogicActionsRule ignores an Action that validates or authorizes', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors)->not->toHaveKey('GuardedActions.php');
});

test('NoSimpleLogicActionsRule ignores an Action that calls two or more collaborators', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors)->not->toHaveKey('CollaboratingActions.php');
});

test('NoSimpleLogicActionsRule ignores an abstract Action, an Action without __invoke() and a class that is not an Action', function (): void {
    $errors = PhpstanFixtureRunner::run(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($errors)->not->toHaveKey('SkippedClasses.php');
});

test('NoSimpleLogicActionsRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(NO_SIMPLE_LOGIC_ACTIONS_FIXTURES, PhpstanFixtureRunner::ISOLATED_RULES_CONFIG);

    expect($identifiers['SimpleActions.php'] ?? [])->toBe(array_fill(0, 5, NoSimpleLogicActionsRule::IDENTIFIER))
        ->and($identifiers['ConfiguredNamespaceActions.php'] ?? [])->toBe([NoSimpleLogicActionsRule::IDENTIFIER]);
});
