<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('NoWhereRawRule blocks whereRaw calls on Eloquent query builder', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoWhereRawRule',
    );

    expect($errors)->toHaveKey('ClassWithWhereRaw.php');

    $forbiddenErrors = $errors['ClassWithWhereRaw.php'];
    $whereRawMessage = 'Method "whereRaw()" is forbidden. Raw where clauses bypass parameter binding '
        . 'and pose a SQL injection risk. Use Eloquent query builder methods instead.';
    $orWhereRawMessage = 'Method "orWhereRaw()" is forbidden. Raw where clauses bypass parameter binding '
        . 'and pose a SQL injection risk. Use Eloquent query builder methods instead.';

    expect($forbiddenErrors)
        ->toContain($whereRawMessage)
        ->toContain($orWhereRawMessage);
});

test('NoWhereRawRule allows safe Eloquent query builder methods', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoWhereRawRule',
    );

    $safeErrors = array_filter(
        $errors['ClassWithSafeQueries.php'] ?? [],
        static fn (string $msg): bool => str_contains($msg, 'whereRaw') || str_contains($msg, 'orWhereRaw'),
    );

    expect($safeErrors)->toBeEmpty();
});

test('NoWhereRawRule blocks whereRaw in Action classes', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/NoWhereRawRule',
    );

    expect($errors)->toHaveKey('ActionWithWhereRaw.php');

    $actionErrors = array_filter(
        $errors['ActionWithWhereRaw.php'],
        static fn (string $msg): bool => str_contains($msg, 'whereRaw'),
    );

    expect($actionErrors)->not->toBeEmpty();
});
