<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\NoHttpResponseInActionsRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

test('NoHttpResponseInActionsRule reports an HTTP response returned from an ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(__DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpResponseInActionsRule');

    // case => [fixture file, method, reported response type]
    $violations = [
        'JsonResponse' => ['ReturnsJsonResponseAction.php', '__invoke', 'Illuminate\Http\JsonResponse'],
        'RedirectResponse' => ['ReturnsRedirectResponseAction.php', '__invoke', 'Illuminate\Http\RedirectResponse'],
        'Symfony Response' => ['ReturnsSymfonyResponseAction.php', '__invoke', 'Symfony\Component\HttpFoundation\Response'],
        'StreamedResponse' => ['ReturnsStreamedResponseAction.php', '__invoke', 'Symfony\Component\HttpFoundation\StreamedResponse'],
        'View' => ['ReturnsViewAction.php', '__invoke', 'Illuminate\Contracts\View\View'],
        'Responsable' => ['ReturnsResponsableAction.php', '__invoke', 'Illuminate\Contracts\Support\Responsable'],
        'JsonResource' => ['ReturnsJsonResourceAction.php', '__invoke', 'Illuminate\Http\Resources\Json\JsonResource'],
        'ResponseFactory' => ['ReturnsResponseFactoryAction.php', '__invoke', 'Illuminate\Contracts\Routing\ResponseFactory'],
        'Inertia Response' => ['ReturnsInertiaResponseAction.php', '__invoke', 'Inertia\Response'],
        'nullable response' => ['ReturnsNullableResponseAction.php', '__invoke', 'Illuminate\Http\JsonResponse'],
        'response in a union' => ['ReturnsUnionWithResponseAction.php', '__invoke', 'Illuminate\Http\JsonResponse'],
        'response behind mixed' => ['ReturnsResponseBehindMixedAction.php', '__invoke', 'Illuminate\Http\JsonResponse'],
        'response behind Stringable' => ['ReturnsResponseBehindStringableAction.php', '__invoke', 'Illuminate\Http\JsonResponse'],
        'private method' => ['ReturnsResponseFromPrivateMethodAction.php', 'buildResponse', 'Illuminate\Http\JsonResponse'],
    ];

    foreach ($violations as [$fixture, $method, $responseType]) {
        $expectedMessage = sprintf(
            'Action class "Pekral\Arch\Tests\Fixtures\PHPStan\NoHttpResponseInActionsRule\%s" method %s() must not return an HTTP response (%s).'
            . ' Return the data and build the HTTP response in the controller.',
            basename($fixture, '.php'),
            $method,
            $responseType,
        );

        expect($errors[$fixture] ?? [])->toBe([$expectedMessage]);
    }
});

test('NoHttpResponseInActionsRule reports every violation with the identifier used to ignore it', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(__DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpResponseInActionsRule');

    $violatingFixtures = array_filter(
        array_keys($identifiers),
        static fn (string $fixture): bool => str_starts_with($fixture, 'Returns'),
    );

    expect($violatingFixtures)->toHaveCount(14);

    foreach ($violatingFixtures as $fixture) {
        expect($identifiers[$fixture])->toBe([NoHttpResponseInActionsRule::IDENTIFIER]);
    }
});

test('NoHttpResponseInActionsRule ignores DTOs, scalars, void and classes that are not an ArchAction', function (): void {
    $errors = PhpstanFixtureRunner::run(__DIR__ . '/../../../tests/fixtures/PHPStan/NoHttpResponseInActionsRule');

    expect($errors)->not->toHaveKey('ValidDtoAction.php')
        ->and($errors)->not->toHaveKey('ValidNullableDtoAction.php')
        ->and($errors)->not->toHaveKey('ValidVoidAction.php')
        ->and($errors)->not->toHaveKey('ValidMixedScalarAction.php')
        ->and($errors)->not->toHaveKey('ValidMixedDtoAction.php')
        ->and($errors)->not->toHaveKey('NotAnActionController.php');
});
