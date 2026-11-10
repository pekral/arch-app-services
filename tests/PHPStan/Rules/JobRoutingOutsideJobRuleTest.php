<?php

declare(strict_types = 1);

use Pekral\Arch\PHPStan\Rules\JobRoutingOutsideJobRule;
use Pekral\Arch\Tests\PHPStan\Helpers\PhpstanFixtureRunner;

const JOB_ROUTING_OUTSIDE_JOB_FIXTURES = __DIR__ . '/../../../tests/fixtures/PHPStan/JobRoutingOutsideJobRule';

function jobRoutingOutsideJobMessage(string $method): string
{
    return sprintf('Queue routing "%s()" must be configured inside the job class, not at the call site.', $method);
}

test('JobRoutingOutsideJobRule reports routing calls on a pending dispatch and on a job instance outside the job', function (): void {
    $errors = PhpstanFixtureRunner::run(JOB_ROUTING_OUTSIDE_JOB_FIXTURES);

    // delay() and a job routed through its $connection / $queue properties add no message.
    expect($errors['ImportController.php'] ?? [])->toBe(array_map(
        jobRoutingOutsideJobMessage(...),
        ['onConnection', 'onQueue', 'onGroup', 'onTenantGroup'],
    ));
});

test('JobRoutingOutsideJobRule allows routing on $this inside the job and reports a self-dispatch routed at the call site', function (): void {
    $errors = PhpstanFixtureRunner::run(JOB_ROUTING_OUTSIDE_JOB_FIXTURES);

    expect($errors['Jobs.php'] ?? [])->toBe([jobRoutingOutsideJobMessage('onConnection')]);
});

test('JobRoutingOutsideJobRule ignores routing-like methods of a class that is not a queued job', function (): void {
    $errors = PhpstanFixtureRunner::run(JOB_ROUTING_OUTSIDE_JOB_FIXTURES);

    expect($errors)->not->toHaveKey('NotAJob.php');
});

test('JobRoutingOutsideJobRule checks only onConnection, onQueue and onGroup without routingMethods', function (): void {
    $errors = PhpstanFixtureRunner::runWithDefaultArguments(JOB_ROUTING_OUTSIDE_JOB_FIXTURES);

    expect($errors['ImportController.php'] ?? [])->toBe(array_map(
        jobRoutingOutsideJobMessage(...),
        ['onConnection', 'onQueue', 'onGroup'],
    ))
        ->and($errors['Jobs.php'] ?? [])->toBe([jobRoutingOutsideJobMessage('onConnection')])
        ->and($errors)->not->toHaveKey('NotAJob.php');
});

test('JobRoutingOutsideJobRule reports each violation with its ignore identifier', function (): void {
    $identifiers = PhpstanFixtureRunner::runIdentifiers(JOB_ROUTING_OUTSIDE_JOB_FIXTURES);

    expect($identifiers['ImportController.php'] ?? [])->toBe(array_fill(0, 4, JobRoutingOutsideJobRule::IDENTIFIER))
        ->and($identifiers['Jobs.php'] ?? [])->toBe([JobRoutingOutsideJobRule::IDENTIFIER]);
});
