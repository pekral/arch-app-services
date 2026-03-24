<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('OnlyRepositoriesCanQueryDataRule blocks query methods only in action classes', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/OnlyRepositoriesCanQueryDataRule',
    );
    $prefix = 'Eloquent query method "%s()" cannot be called in Action classes. Found in: '
        . 'Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule\\';

    expect($errors)->toHaveKey('ActionWithQuery.php');

    $actionErrors = $errors['ActionWithQuery.php'];
    expect($actionErrors)
        ->toContain(sprintf($prefix . 'ActionWithQuery', 'where'))
        ->toContain(sprintf($prefix . 'ActionWithQuery', 'orderBy'))
        ->toContain(sprintf($prefix . 'ActionWithQuery', 'get'))
        ->toContain(sprintf($prefix . 'ActionWithQuery', 'find'));

    expect($errors)->not->toHaveKey('ControllerWithQuery.php');
    expect($errors)->not->toHaveKey('ControllerWithStaticQuery.php');
    expect($errors)->not->toHaveKey('ServiceWithQuery.php');

    expect($errors)->not->toHaveKey('ValidRepository.php');
    expect($errors)->not->toHaveKey('ValidModelManager.php');
    expect($errors)->not->toHaveKey('ValidModelService.php');
    expect($errors)->not->toHaveKey('ClassWithSafeBuilderOnly.php');
});
