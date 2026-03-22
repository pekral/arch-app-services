<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('OnlyRepositoriesCanQueryDataRule blocks query methods outside allowed classes', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/OnlyRepositoriesCanQueryDataRule',
    );
    $prefix = 'Eloquent query method "%s()" can only be called in Repository, '
        . 'ModelManager, or ModelService classes. Found in: '
        . 'Pekral\Arch\Tests\Fixtures\PHPStan\OnlyRepositoriesCanQueryDataRule\\';

    expect($errors)->toHaveKey('ControllerWithQuery.php');

    $controllerErrors = $errors['ControllerWithQuery.php'];
    expect($controllerErrors)
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'where'))
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'orderBy'))
        ->toContain(sprintf($prefix . 'ControllerWithQuery', 'get'));

    expect($errors)->toHaveKey('ControllerWithStaticQuery.php');
    expect($errors['ControllerWithStaticQuery.php'])
        ->toContain(sprintf($prefix . 'ControllerWithStaticQuery', 'find'));

    expect($errors)->toHaveKey('ServiceWithQuery.php');
    expect($errors['ServiceWithQuery.php'])
        ->toContain(sprintf($prefix . 'ServiceWithQuery', 'whereIn'))
        ->toContain(sprintf($prefix . 'ServiceWithQuery', 'get'));

    expect($errors)->not->toHaveKey('ValidRepository.php');
    expect($errors)->not->toHaveKey('ValidModelManager.php');
    expect($errors)->not->toHaveKey('ValidModelService.php');
    expect($errors)->not->toHaveKey('ClassWithSafeBuilderOnly.php');
});
