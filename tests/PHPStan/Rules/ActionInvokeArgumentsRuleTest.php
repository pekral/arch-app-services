<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('ActionInvokeArgumentsRule forbids passing arguments when invoking an ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/ActionInvokeArgumentsRule',
    );

    $expectedMessage = 'ArchAction must be invoked without arguments. Provide all inputs via constructor injection and call the action as $action().';

    // Valid invocation — no errors expected
    expect($errors)->not->toHaveKey('ValidActionInvocation.php');

    // Non-action callable with arguments — no errors expected
    expect($errors)->not->toHaveKey('NonActionCalledWithArguments.php');

    // Action called with arguments via normal syntax
    expect($errors['ActionCalledWithArguments.php'] ?? [])->toContain($expectedMessage);

    // Action called with arguments via parenthesized syntax: ($action)($arg1, $arg2)
    expect($errors['ActionCalledWithParenthesizedSyntax.php'] ?? [])->toContain($expectedMessage);
});
