<?php

declare(strict_types = 1);

use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('WhereRawBindingsRule allows static string literals without bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    $ruleErrors = array_filter(
        $errors['WhereRawWithStaticString.php'] ?? [],
        static fn (string $msg): bool => str_contains($msg, 'requires bindings'),
    );

    expect($ruleErrors)->toBeEmpty();
});

test('WhereRawBindingsRule allows dynamic expressions with bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    $ruleErrors = array_filter(
        $errors['WhereRawWithBindings.php'] ?? [],
        static fn (string $msg): bool => str_contains($msg, 'requires bindings'),
    );

    expect($ruleErrors)->toBeEmpty();
});

test('WhereRawBindingsRule flags string concatenation without bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    expect($errors)->toHaveKey('WhereRawWithConcatenation.php');

    $ruleErrors = $errors['WhereRawWithConcatenation.php'];
    expect($ruleErrors)->toContain(
        'Call to whereRaw() with a dynamic expression requires bindings (second argument) to prevent SQL injection.',
    );
});

test('WhereRawBindingsRule flags interpolated strings without bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    expect($errors)->toHaveKey('WhereRawWithInterpolation.php');

    $ruleErrors = $errors['WhereRawWithInterpolation.php'];
    expect($ruleErrors)->toContain(
        'Call to whereRaw() with a dynamic expression requires bindings (second argument) to prevent SQL injection.',
    );
});

test('WhereRawBindingsRule flags variable arguments without bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    expect($errors)->toHaveKey('WhereRawWithVariable.php');

    $ruleErrors = $errors['WhereRawWithVariable.php'];
    expect($ruleErrors)
        ->toContain('Call to whereRaw() with a dynamic expression requires bindings (second argument) to prevent SQL injection.')
        ->toContain('Call to orWhereRaw() with a dynamic expression requires bindings (second argument) to prevent SQL injection.');
});

test('WhereRawBindingsRule flags function call results without bindings', function (): void {
    $errors = PhpstanFixtureRunner::run(
        __DIR__ . '/../../../tests/fixtures/PHPStan/WhereRawBindingsRule',
    );

    expect($errors)->toHaveKey('WhereRawWithFunctionCall.php');

    $ruleErrors = $errors['WhereRawWithFunctionCall.php'];
    expect($ruleErrors)->toContain(
        'Call to whereRaw() with a dynamic expression requires bindings (second argument) to prevent SQL injection.',
    );
});
