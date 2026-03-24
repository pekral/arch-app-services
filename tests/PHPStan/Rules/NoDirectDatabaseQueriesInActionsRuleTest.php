<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('NoDirectDatabaseQueriesInActionsRule blocks forbidden query builder methods in Actions', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoDirectDatabaseQueriesInActionsRule',
    );

    expect($errors)->toHaveKey('ActionWithForbiddenQueryMethods.php');

    $forbiddenErrors = $errors['ActionWithForbiddenQueryMethods.php'];
    expect($forbiddenErrors)
        ->toContain('Query builder method "where()" cannot be called in Action classes. Data retrieval with conditions must be in Repository class.')
        ->toContain('Query builder method "orderBy()" cannot be called in Action classes. Data retrieval with conditions must be in Repository class.');
});

test('NoDirectDatabaseQueriesInActionsRule blocks scope chained before retrieval on a query builder in Actions', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoDirectDatabaseQueriesInActionsRule',
    );

    expect($errors)->toHaveKey('ActionWithBuilderScopeChain.php');

    $scopeErrors = $errors['ActionWithBuilderScopeChain.php'];
    expect($scopeErrors)->toContain(
        'Eloquent scope "active()" cannot be called in Action classes. Data retrieval with conditions must be in Repository class.',
    );
});

test('NoDirectDatabaseQueriesInActionsRule allows scope calls on model instances in Actions', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoDirectDatabaseQueriesInActionsRule',
    );

    $instanceErrors = $errors['ActionWithScopeOnModelInstance.php'] ?? [];
    $scopeErrors = array_filter(
        $instanceErrors,
        static fn (string $msg): bool => str_contains($msg, 'Eloquent scope "active()"'),
    );

    expect($scopeErrors)->toBeEmpty();
});
