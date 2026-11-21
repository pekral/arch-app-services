<?php

declare(strict_types = 1);

use Illuminate\Support\Facades\Config;
use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Tests\Models\User;

test('the bulk delete example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    User::factory()->count(2)->create(['name' => 'John']);
    User::factory()->create(['name' => 'Jane']);

    $service->bulkDeleteByParams(['name' => 'John']);

    expect(User::query()->where('name', 'John')->count())->toBe(0)
        ->and(User::query()->where('name', 'Jane')->count())->toBe(1);
});

test('the verified user count example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    User::factory()->count(2)->create(['email_verified_at' => null]);
    User::factory()->count(3)->create(['email_verified_at' => now()]);

    $count = $service->countByParams([['email_verified_at', '!=', null]]);

    expect($count)->toBe(3);
});

test('the cached verified user count example uses the repository cache directly', function (): void {
    Config::set('cache.default', 'array');
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'direct_example_count');
    User::insert([
        ...User::factory()->count(2)->raw(['email_verified_at' => '2026-01-01 00:00:00']),
        ...User::factory()->count(2)->raw(['email_verified_at' => null]),
    ]);
    $repository = app(UserModelService::class)->getRepository()->cache();
    $params = [['email_verified_at', '!=', null]];

    $coldResult = $repository->countByParams($params);
    User::insert(User::factory()->raw(['email_verified_at' => '2026-01-01 00:00:00']));
    $warmResult = $repository->countByParams($params);

    expect($coldResult)->toBe(2)
        ->and(User::query()->whereNotNull('email_verified_at')->count())->toBe(3)
        ->and($warmResult)->toBe(2);
});

test('the model manager delete example uses UserModelManager directly', function (): void {
    $manager = app(UserModelManager::class);
    $user = User::factory()->create();

    $deleted = $manager->delete($user);

    expect($deleted)->toBeTrue()
        ->and(User::query()->find($user->id))->toBeNull();
});

test('the get user example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    $user = User::factory()->create();

    $result = $service->getOneByParams(['id' => $user->id]);

    expect($result->id)->toBe($user->id);
});

test('the cached get user example uses the repository cache directly', function (): void {
    Config::set('cache.default', 'array');
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'direct_example_get');
    $email = 'cached-get@example.com';
    User::insert([
        User::factory()->raw(['email' => 'not-cached-get@example.com']),
        User::factory()->raw(['email' => $email]),
    ]);
    $repository = app(UserModelService::class)->getRepository()->cache();

    $coldResult = $repository->getOneByParams(['email' => $email]);
    User::query()->where('email', $email)->delete();
    $warmResult = $repository->getOneByParams(['email' => $email]);

    expect($coldResult->email)->toBe($email)
        ->and(User::query()->where('email', $email)->count())->toBe(0)
        ->and($warmResult->email)->toBe($email);
});

test('the paginated users example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    User::factory()->count(3)->create();

    $result = $service->paginateByParams([]);

    expect($result)->toHaveCount(3);
});

test('the cached paginated users example uses the repository cache directly', function (): void {
    Config::set('cache.default', 'array');
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'direct_example_paginate');
    User::insert([
        ...User::factory()->count(2)->raw(['name' => 'Cached']),
        User::factory()->raw(['name' => 'Not cached']),
    ]);
    $repository = app(UserModelService::class)->getRepository()->cache();

    $coldResult = $repository->paginateByParams(['name' => 'Cached']);
    User::insert(User::factory()->raw(['name' => 'Cached']));
    $warmResult = $repository->paginateByParams(['name' => 'Cached']);

    expect($coldResult->total())->toBe(2)
        ->and(User::query()->where('name', 'Cached')->count())->toBe(3)
        ->and($warmResult->total())->toBe(2);
});

test('the bulk import example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    $users = [
        ['email' => 'first@example.com', 'name' => 'First', 'password' => 'password'],
        ['email' => 'second@example.com', 'name' => 'Second', 'password' => 'password'],
    ];

    $created = $service->bulkCreate($users);

    expect($created)->toBe(2);
});

test('the bulk refresh example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    $user = User::factory()->create(['name' => 'Before']);

    $updated = $service->bulkUpdate([['id' => $user->id, 'name' => 'After']]);

    expect($updated)->toBe(1)
        ->and(User::query()->findOrFail($user->id)->name)->toBe('After');
});

test('the user search example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    $user = User::factory()->create();

    $result = $service->findOneByParams(['id' => $user->id]);

    expect($result)->not->toBeNull()
        ->and($result?->id)->toBe($user->id);
});

test('the cached user search example uses the repository cache directly', function (): void {
    Config::set('cache.default', 'array');
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'direct_example_search');
    $email = 'cached-search@example.com';
    User::insert([
        User::factory()->raw(['email' => 'not-cached-search@example.com']),
        User::factory()->raw(['email' => $email]),
    ]);
    $repository = app(UserModelService::class)->getRepository()->cache();

    $coldResult = $repository->findOneByParams(['email' => $email]);
    User::query()->where('email', $email)->delete();
    $warmResult = $repository->findOneByParams(['email' => $email]);

    expect($coldResult?->email)->toBe($email)
        ->and(User::query()->where('email', $email)->count())->toBe(0)
        ->and($warmResult?->email)->toBe($email);
});
