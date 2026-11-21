<?php

declare(strict_types = 1);

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
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
    $cache = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cache);
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');

    $cache->shouldReceive('remember')->once()->andReturn(3);

    $result = app(UserModelService::class)->getRepository()->cache()->countByParams([['email_verified_at', '!=', null]]);

    expect($result)->toBe(3);
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
    $cache = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cache);
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    $user = User::factory()->create();
    $cache->shouldReceive('remember')->once()->andReturn($user);

    $result = app(UserModelService::class)->getRepository()->cache()->getOneByParams(['id' => $user->id]);

    expect($result)->not->toBeNull()
        ->and($result?->id)->toBe($user->id);
});

test('the paginated users example uses UserModelService directly', function (): void {
    $service = app(UserModelService::class);
    User::factory()->count(3)->create();

    $result = $service->paginateByParams([]);

    expect($result)->toHaveCount(3);
});

test('the cached paginated users example uses the repository cache directly', function (): void {
    $cache = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cache);
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    $expected = new LengthAwarePaginator(collect(), 3, 15);
    $cache->shouldReceive('remember')->once()->andReturn($expected);

    $result = app(UserModelService::class)->getRepository()->cache()->paginateByParams([]);

    expect($result)->toBe($expected);
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
    $cache = Mockery::mock(CacheRepository::class);
    Cache::shouldReceive('store')->andReturn($cache);
    Config::set('arch.repository_cache.enabled', value: true);
    Config::set('arch.repository_cache.ttl', 3_600);
    Config::set('arch.repository_cache.prefix', 'arch_repo');
    $user = User::factory()->create();
    $cache->shouldReceive('remember')->once()->andReturn($user);

    $result = app(UserModelService::class)->getRepository()->cache()->findOneByParams(['id' => $user->id]);

    expect($result)->toBe($user);
});
