<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Services;

use Pekral\Arch\Examples\Services\User\UserModelManager;
use Pekral\Arch\Examples\Services\User\UserModelService;
use Pekral\Arch\Examples\Services\User\UserRepository;
use Pekral\Arch\Tests\Models\User;
use Pekral\Arch\Tests\TestCase;
use ReflectionClass;

final class UserModelServiceTest extends TestCase
{

    private UserModelService $userModelService;

    public function testGetModelManager(): void
    {
        $manager = $this->userModelService->getModelManager();

        $this->assertInstanceOf(UserModelManager::class, $manager);
    }

    public function testGetRepository(): void
    {
        $repository = $this->userModelService->getRepository();

        $this->assertInstanceOf(UserRepository::class, $repository);
    }

    public function testCreate(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'password123',
        ];

        $user = $this->userModelService->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    public function testGetModelClass(): void
    {
        $reflection = new ReflectionClass($this->userModelService);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);

        $modelClass = $method->invoke($this->userModelService);

        $this->assertSame(User::class, $modelClass);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModelService = app(UserModelService::class);
    }

}
