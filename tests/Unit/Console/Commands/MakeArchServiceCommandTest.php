<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Console\Commands;

use Pekral\Arch\Tests\TestCase;

use function file_exists;
use function file_get_contents;
use function is_dir;
use function rmdir;
use function unlink;

final class MakeArchServiceCommandTest extends TestCase
{

    private string $generatedPath;

    public function testCreatesCompleteServiceStack(): void
    {
        $this->artisan('make:arch-service', ['model' => 'App\Models\Product'])
            ->assertSuccessful();

        $modelManagerPath = $this->generatedPath . '/Product/ProductModelManager.php';
        $repositoryPath = $this->generatedPath . '/Product/ProductRepository.php';
        $servicePath = $this->generatedPath . '/Product/ProductModelService.php';

        $this->assertFileExists($modelManagerPath);
        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($servicePath);

        $modelManagerContent = file_get_contents($modelManagerPath);
        $this->assertStringContainsString('namespace App\Services\Product;', $modelManagerContent);
        $this->assertStringContainsString('final class ProductModelManager extends BaseModelManager', $modelManagerContent);
        $this->assertStringContainsString('return Product::class;', $modelManagerContent);

        $repositoryContent = file_get_contents($repositoryPath);
        $this->assertStringContainsString('namespace App\Services\Product;', $repositoryContent);
        $this->assertStringContainsString('final class ProductRepository extends BaseRepository', $repositoryContent);
        $this->assertStringContainsString('use CacheableRepository;', $repositoryContent);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('namespace App\Services\Product;', $serviceContent);
        $this->assertStringContainsString('final readonly class ProductModelService extends BaseModelService', $serviceContent);
        $this->assertStringContainsString('private ProductModelManager $productModelManager', $serviceContent);
        $this->assertStringContainsString('private ProductRepository $productRepository', $serviceContent);
    }

    public function testCreatesServiceWithoutRepository(): void
    {
        $this->artisan('make:arch-service', [
            '--no-repository' => true,
            'model' => 'App\Models\Order',
        ])
            ->assertSuccessful();

        $modelManagerPath = $this->generatedPath . '/Order/OrderModelManager.php';
        $repositoryPath = $this->generatedPath . '/Order/OrderRepository.php';
        $servicePath = $this->generatedPath . '/Order/OrderModelService.php';

        $this->assertFileExists($modelManagerPath);
        $this->assertFileDoesNotExist($repositoryPath);
        $this->assertFileExists($servicePath);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('throw new \RuntimeException', $serviceContent);
        $this->assertStringContainsString('Repository not configured', $serviceContent);
    }

    public function testCreatesServiceWithoutModelManager(): void
    {
        $this->artisan('make:arch-service', [
            '--no-model-manager' => true,
            'model' => 'App\Models\Category',
        ])
            ->assertSuccessful();

        $modelManagerPath = $this->generatedPath . '/Category/CategoryModelManager.php';
        $repositoryPath = $this->generatedPath . '/Category/CategoryRepository.php';
        $servicePath = $this->generatedPath . '/Category/CategoryModelService.php';

        $this->assertFileDoesNotExist($modelManagerPath);
        $this->assertFileExists($repositoryPath);
        $this->assertFileExists($servicePath);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('throw new \RuntimeException', $serviceContent);
        $this->assertStringContainsString('ModelManager not configured', $serviceContent);
    }

    public function testCreatesMinimalService(): void
    {
        $this->artisan('make:arch-service', [
            '--no-model-manager' => true,
            '--no-repository' => true,
            'model' => 'App\Models\Tag',
        ])
            ->assertSuccessful();

        $modelManagerPath = $this->generatedPath . '/Tag/TagModelManager.php';
        $repositoryPath = $this->generatedPath . '/Tag/TagRepository.php';
        $servicePath = $this->generatedPath . '/Tag/TagModelService.php';

        $this->assertFileDoesNotExist($modelManagerPath);
        $this->assertFileDoesNotExist($repositoryPath);
        $this->assertFileExists($servicePath);

        $serviceContent = file_get_contents($servicePath);
        $this->assertStringContainsString('ModelManager not configured', $serviceContent);
        $this->assertStringContainsString('Repository not configured', $serviceContent);
    }

    public function testSkipsExistingFilesWithoutForce(): void
    {
        $this->artisan('make:arch-service', ['model' => 'App\Models\Customer'])
            ->assertSuccessful();

        $this->artisan('make:arch-service', ['model' => 'App\Models\Customer'])
            ->assertFailed();
    }

    public function testOverwritesExistingFilesWithForce(): void
    {
        $this->artisan('make:arch-service', ['model' => 'App\Models\Invoice'])
            ->assertSuccessful();

        $this->artisan('make:arch-service', [
            '--force' => true,
            'model' => 'App\Models\Invoice',
        ])
            ->assertSuccessful();

        $servicePath = $this->generatedPath . '/Invoice/InvoiceModelService.php';
        $this->assertFileExists($servicePath);
    }

    public function testOutputsGeneratedFiles(): void
    {
        $this->artisan('make:arch-service', ['model' => 'App\Models\Payment'])
            ->expectsOutputToContain('Generating service stack for Payment')
            ->expectsOutputToContain('Generated files:')
            ->assertSuccessful();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->generatedPath = $this->app->basePath('app/Services');
    }

    protected function tearDown(): void
    {
        $this->cleanupGeneratedFiles();

        parent::tearDown();
    }

    private function cleanupGeneratedFiles(): void
    {
        $models = ['Category', 'Customer', 'Invoice', 'Order', 'Payment', 'Product', 'Tag'];

        foreach ($models as $model) {
            $this->cleanupModelFiles($model);
        }

        $this->removeDirectoryIfEmpty($this->generatedPath);
    }

    private function cleanupModelFiles(string $model): void
    {
        $files = [
            $this->generatedPath . '/' . $model . '/' . $model . 'ModelManager.php',
            $this->generatedPath . '/' . $model . '/' . $model . 'ModelService.php',
            $this->generatedPath . '/' . $model . '/' . $model . 'Repository.php',
        ];

        foreach ($files as $file) {
            $this->removeFileIfExists($file);
        }

        $this->removeDirectoryIfEmpty($this->generatedPath . '/' . $model);
    }

    private function removeFileIfExists(string $file): void
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function removeDirectoryIfEmpty(string $directory): void
    {
        if (is_dir($directory) && $this->isDirectoryEmpty($directory)) {
            rmdir($directory);
        }
    }

    private function isDirectoryEmpty(string $directory): bool
    {
        $files = scandir($directory);

        return $files !== false && count($files) === 2;
    }

}
