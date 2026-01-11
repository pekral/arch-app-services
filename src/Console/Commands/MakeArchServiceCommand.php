<?php

declare(strict_types = 1);

namespace Pekral\Arch\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

use function app_path;
use function array_keys;
use function array_values;
use function class_basename;
use function dirname;
use function file_exists;
use function str_replace;

final class MakeArchServiceCommand extends Command
{

    /**
     * @var string
     */
    protected $signature = 'make:arch-service
        {model : The fully qualified model class name (e.g., App\Models\User)}
        {--no-repository : Skip repository generation}
        {--no-model-manager : Skip model manager generation}
        {--force : Overwrite existing files}';

    /**
     * @var string
     */
    protected $description = 'Create a complete service stack (Service, Repository, ModelManager) for a model';

    public function __construct(private readonly Filesystem $filesystem)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $modelBaseName = class_basename($modelClass);

        $this->info(sprintf('Generating service stack for %s...', $modelBaseName));

        $generatedFiles = $this->generateServiceStack($modelClass, $modelBaseName);

        return $this->outputGeneratedFiles($generatedFiles);
    }

    /**
     * @return array<int, string>
     */
    private function generateServiceStack(string $modelClass, string $modelBaseName): array
    {
        $generatedFiles = [];

        if (!$this->option('no-model-manager')) {
            $modelManagerPath = $this->generateModelManager($modelClass, $modelBaseName);

            if ($modelManagerPath !== null) {
                $generatedFiles[] = $modelManagerPath;
            }
        }

        if (!$this->option('no-repository')) {
            $repositoryPath = $this->generateRepository($modelClass, $modelBaseName);

            if ($repositoryPath !== null) {
                $generatedFiles[] = $repositoryPath;
            }
        }

        $servicePath = $this->generateService($modelClass, $modelBaseName);

        if ($servicePath !== null) {
            $generatedFiles[] = $servicePath;
        }

        return $generatedFiles;
    }

    /**
     * @param array<int, string> $generatedFiles
     */
    private function outputGeneratedFiles(array $generatedFiles): int
    {
        if ($generatedFiles === []) {
            $this->error('No files were generated.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Generated files:');

        foreach ($generatedFiles as $file) {
            $this->line('  - ' . $file);
        }

        return self::SUCCESS;
    }

    private function generateModelManager(string $modelClass, string $modelBaseName): ?string
    {
        $className = $modelBaseName . 'ModelManager';
        $namespace = $this->resolveServiceNamespace($modelBaseName);
        $path = $this->resolveServicePath($modelBaseName, $className);

        if (!$this->shouldGenerateFile($path, $className)) {
            return null;
        }

        $stub = $this->filesystem->get(__DIR__ . '/../../stubs/model-manager.stub');
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ class }}' => $className,
            '{{ modelBaseName }}' => $modelBaseName,
            '{{ modelClass }}' => $modelClass,
            '{{ namespace }}' => $namespace,
        ]);

        $this->ensureDirectoryExists($path);
        $this->filesystem->put($path, $content);
        $this->components->info(sprintf('ModelManager [%s] created successfully.', $path));

        return $path;
    }

    private function generateRepository(string $modelClass, string $modelBaseName): ?string
    {
        $className = $modelBaseName . 'Repository';
        $namespace = $this->resolveServiceNamespace($modelBaseName);
        $path = $this->resolveServicePath($modelBaseName, $className);

        if (!$this->shouldGenerateFile($path, $className)) {
            return null;
        }

        $stub = $this->filesystem->get(__DIR__ . '/../../stubs/repository.stub');
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ class }}' => $className,
            '{{ modelBaseName }}' => $modelBaseName,
            '{{ modelClass }}' => $modelClass,
            '{{ namespace }}' => $namespace,
        ]);

        $this->ensureDirectoryExists($path);
        $this->filesystem->put($path, $content);
        $this->components->info(sprintf('Repository [%s] created successfully.', $path));

        return $path;
    }

    private function generateService(string $modelClass, string $modelBaseName): ?string
    {
        $className = $modelBaseName . 'ModelService';
        $namespace = $this->resolveServiceNamespace($modelBaseName);
        $path = $this->resolveServicePath($modelBaseName, $className);

        if (!$this->shouldGenerateFile($path, $className)) {
            return null;
        }

        $stubFile = $this->resolveServiceStubFile();

        $stub = $this->filesystem->get(__DIR__ . '/../../stubs/' . $stubFile);
        $content = $this->replaceStubPlaceholders($stub, [
            '{{ class }}' => $className,
            '{{ modelBaseName }}' => $modelBaseName,
            '{{ modelClass }}' => $modelClass,
            '{{ modelManagerClass }}' => $modelBaseName . 'ModelManager',
            '{{ modelManagerProperty }}' => Str::camel($modelBaseName) . 'ModelManager',
            '{{ namespace }}' => $namespace,
            '{{ repositoryClass }}' => $modelBaseName . 'Repository',
            '{{ repositoryProperty }}' => Str::camel($modelBaseName) . 'Repository',
        ]);

        $this->ensureDirectoryExists($path);
        $this->filesystem->put($path, $content);
        $this->components->info(sprintf('Service [%s] created successfully.', $path));

        return $path;
    }

    private function resolveServiceStubFile(): string
    {
        $hasRepository = !$this->option('no-repository');
        $hasModelManager = !$this->option('no-model-manager');

        return match (true) {
            $hasRepository && $hasModelManager => 'service.stub',
            $hasRepository => 'service-repository-only.stub',
            $hasModelManager => 'service-model-manager-only.stub',
            default => 'service-minimal.stub',
        };
    }

    private function resolveServiceNamespace(string $modelBaseName): string
    {
        return 'App\Services\\' . $modelBaseName;
    }

    private function resolveServicePath(string $modelBaseName, string $className): string
    {
        return app_path('Services/' . $modelBaseName . '/' . $className . '.php');
    }

    private function shouldGenerateFile(string $path, string $className): bool
    {
        if (!file_exists($path)) {
            return true;
        }

        if ($this->option('force')) {
            return true;
        }

        $this->components->warn($className . ' already exists. Use --force to overwrite.');

        return false;
    }

    private function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);

        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }
    }

    /**
     * @param array<string, string> $replacements
     */
    private function replaceStubPlaceholders(string $stub, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub,
        );
    }

}
