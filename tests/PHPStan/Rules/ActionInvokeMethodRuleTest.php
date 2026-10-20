<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('ActionInvokeMethodRule enforces invoke-only and explicit return type', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/ActionInvokeMethodRule',
    );

    // Valid action — no rule errors expected
    expect($errors)->not->toHaveKey('ValidFinalReadonlyAction.php');

    // Missing return type on __invoke()
    expect($errors['MissingReturnTypeAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\MissingReturnTypeAction" '
        . 'must declare an explicit return type on "__invoke()".',
    );

    // No public methods at all
    expect($errors['NoPublicMethodAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\NoPublicMethodAction" '
        . 'must declare a public "__invoke()" method and no other public methods.',
    );

    // Public method named other than __invoke
    expect($errors['WrongMethodNameAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\WrongMethodNameAction" '
        . 'must use only public "__invoke()" as its entry point, "handle()" given.',
    );

    // More than one public method
    expect($errors['MultiplePublicMethodsAction.php'] ?? [])->toContain(
        'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionInvokeMethodRule\MultiplePublicMethodsAction" '
        . 'must not declare public methods other than "__invoke()", but found: __invoke, extra.',
    );
});
