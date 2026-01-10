<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Console\Commands;

use Pekral\Arch\Tests\TestCase;

use function file_exists;
use function file_get_contents;
use function is_dir;
use function rmdir;
use function unlink;

final class MakeArchValidationRulesCommandTest extends TestCase
{

    private string $generatedPath;

    public function testCreatesValidationRulesClass(): void
    {
        $this->artisan('make:arch-validation-rules', ['name' => 'TestValidationRules'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/TestValidationRules.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\Rules;', $content);
        $this->assertStringContainsString('final class TestValidationRules implements ValidationRules', $content);
        $this->assertStringContainsString('use Pekral\Arch\DataValidation\ValidationRules;', $content);
        $this->assertStringContainsString('public static function rules(): array', $content);
    }

    public function testCreatesValidationRulesInSubdirectory(): void
    {
        $this->artisan('make:arch-validation-rules', ['name' => 'User/UserValidationRules'])
            ->assertSuccessful();

        $filePath = $this->generatedPath . '/User/UserValidationRules.php';
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace App\Rules\User;', $content);
        $this->assertStringContainsString('final class UserValidationRules implements ValidationRules', $content);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = $this->app->basePath('app/Rules');
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    private function cleanupGeneratedFiles(): void
    {
        $files = [
            $this->generatedPath . '/TestValidationRules.php',
            $this->generatedPath . '/User/UserValidationRules.php',
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
