<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Console\Commands;

use Pekral\Arch\Tests\TestCase;

use function file_exists;
use function file_get_contents;
use function is_dir;
use function rmdir;
use function unlink;

final class MakeArchActionCommandTest extends TestCase
{

    private string $generatedPath;

    public function testCreatesActionClass(): void
    {
        $this->artisan('make:arch-action', ['name' => 'TestAction'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/TestAction.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\Actions;', $content);
        $this->assertStringContainsString('final readonly class TestAction implements ArchAction', $content);
        $this->assertStringContainsString('use Pekral\Arch\Action\ArchAction;', $content);
        $this->assertStringContainsString('public function execute(): void', $content);
    }

    public function testCreatesActionInSubdirectory(): void
    {
        $this->artisan('make:arch-action', ['name' => 'User/CreateUserAction'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/User/CreateUserAction.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\Actions\User;', $content);
        $this->assertStringContainsString('final readonly class CreateUserAction implements ArchAction', $content);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = $this->app->basePath('app/Actions');
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    private function cleanupGeneratedFiles(): void
    {
        $files = [
            $this->generatedPath . '/TestAction.php',
            $this->generatedPath . '/User/CreateUserAction.php',
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
