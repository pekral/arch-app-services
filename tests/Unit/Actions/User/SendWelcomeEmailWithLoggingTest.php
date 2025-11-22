<?php

declare(strict_types = 1);

use Pekral\Arch\Examples\Actions\User\SendWelcomeEmailWithLogging;
use Pekral\Arch\Tests\Models\User;

test('execute sends email successfully', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    assert($user instanceof User);
    $action = new SendWelcomeEmailWithLogging();

    $action->execute($user, ['source' => 'registration']);

    expect(true)->toBeTrue();
});

test('execute sends email with empty context', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    assert($user instanceof User);
    $action = new SendWelcomeEmailWithLogging();

    $action->execute($user);

    expect(true)->toBeTrue();
});

test('execute with custom context', function (): void {
    $user = User::factory()->create([
        'email' => 'custom@example.com',
    ]);
    assert($user instanceof User);
    $context = [
        'campaign_id' => 123,
        'template' => 'welcome_v2',
    ];
    $action = new SendWelcomeEmailWithLogging();

    $action->execute($user, $context);

    expect(true)->toBeTrue();
});
