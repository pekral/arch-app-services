<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Console\Commands;

use Pekral\Arch\Tests\TestCase;

use function file_exists;
use function file_get_contents;
use function is_dir;
use function rmdir;
use function unlink;

final class MakeArchDtoCommandTest extends TestCase
{

    private string $generatedPath;

    public function testCreatesDtoClass(): void
    {
        $this->artisan('make:arch-dto', ['name' => 'TestDto'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/TestDto.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\DTO;', $content);
        $this->assertStringContainsString('final class TestDto extends DataTransferObject', $content);
        $this->assertStringContainsString('use Pekral\Arch\DTO\DataTransferObject;', $content);
        $this->assertStringContainsString('public function __construct(', $content);
    }

    public function testCreatesDtoInSubdirectory(): void
    {
        $this->artisan('make:arch-dto', ['name' => 'User/CreateUserDto'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/User/CreateUserDto.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\DTO\User;', $content);
        $this->assertStringContainsString('final class CreateUserDto extends DataTransferObject', $content);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = $this->app->basePath('app/DTO');
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    private function cleanupGeneratedFiles(): void
    {
        $files = [
            $this->generatedPath . '/TestDto.php',
            $this->generatedPath . '/User/CreateUserDto.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $directories = [
            $this->generatedPath . '/User',
            $this->generatedPath,
        ];

        foreach ($directories as $directory) {
            if (is_dir($directory) && $this->isDirectoryEmpty($directory)) {
                rmdir($directory);
            }
        }
    }

    private function isDirectoryEmpty(string $directory): bool
    {
        $files = scandir($directory);

        return $files !== false && count($files) === 2;
    }

}
