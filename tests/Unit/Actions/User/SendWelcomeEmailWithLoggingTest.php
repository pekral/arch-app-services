<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Log;
use Pekral\Arch\Examples\Actions\User\SendWelcomeEmailWithLogging;
use Pekral\Arch\Tests\Models\User;

test('execute sends email successfully and logs actions', function (): void {
    Log::spy();
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    assert($user instanceof User);
    $action = new SendWelcomeEmailWithLogging();

    $action->execute($user, ['source' => 'registration']);

    Log::shouldHaveReceived('channel')->times(2)->with('stack');
});

test('execute sends email with empty context', function (): void {
    Log::spy();
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);
    assert($user instanceof User);
    $action = new SendWelcomeEmailWithLogging();

    $action->execute($user);

    Log::shouldHaveReceived('channel')->times(2)->with('stack');
});

test('execute logs when logging disabled', function (): void {
    config(['arch.action_logging.enabled' => false]);
    $user = User::factory()->create();
    assert($user instanceof User);
    $action = new SendWelcomeEmailWithLogging();
    Log::spy();

    $action->execute($user);

    Log::shouldNotHaveReceived('channel');
});

test('execute with custom context logs correctly', function (): void {
    Log::spy();
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

    Log::shouldHaveReceived('channel')->times(2)->with('stack');
});
