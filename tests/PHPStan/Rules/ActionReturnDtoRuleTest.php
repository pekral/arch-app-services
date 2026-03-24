<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('ActionReturnDtoRule forbids array return type in ArchAction __invoke', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/ActionReturnDtoRule',
    );

    $expectedMessage = 'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\ActionReturnDtoRule\ArrayReturnAction"'
        . ' __invoke() must not return array. Use a DTO extending "Spatie\LaravelData\Data" instead.';

    // Valid action returning void — no errors expected
    expect($errors)->not->toHaveKey('ValidVoidAction.php');

    // Valid action returning a DTO — no errors expected
    expect($errors)->not->toHaveKey('ValidDtoAction.php');

    // Action returning array — error expected
    expect($errors['ArrayReturnAction.php'] ?? [])->toContain($expectedMessage);
});
