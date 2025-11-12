<?php

declare(strict_types = 1);

namespace Pekral\Arch\Command;

use Illuminate\Support\Collection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function assert;
use function basename;
use function exec;
use function file_exists;
use function file_get_contents;
use function getcwd;
use function is_string;
use function iterator_to_array;
use function realpath;
use function str_contains;
use function str_ends_with;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class CheckActionCoverageCommand extends Command
{

    private const float FULL_COVERAGE_THRESHOLD = 100.0;

    private const float ZERO_COVERAGE = 0.0;

    private const string PHPUNIT_CONFIG_FILE = 'phpunit.xml';

    private const string TESTS_DIRECTORY = 'tests/Unit/Actions';

    private const string COVERAGE_FILE_PREFIX = 'action-coverage-';

    private const string FILE_EXTENSION_PHP = '.php';

    private const string EXCLUDED_DIRECTORY_COMMAND = '/Command/';

    private const string EXCLUDED_DIRECTORY_PIPES = '/Pipes/';

    private const string ARCH_ACTION_INTERFACE = 'implements ArchAction';

    private const string XPATH_FILE = '//file';

    private const string PHP_COVERAGE_OPTIONS = '-dpcov.enabled=1 -dpcov.directory=. -dxdebug.mode=off';

    protected function configure(): void
    {
        $this
            ->setName('arch:check-action-coverage')
            ->setDescription('Run tests and check that all Action classes have 100% code coverage')
            ->setHelp('This command runs tests for Action classes and verifies 100% test coverage.')
            ->addArgument('source', InputArgument::REQUIRED, 'Path to the source directory containing Action classes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourcePath = $input->getArgument('source');
        assert(is_string($sourcePath));

        if (!$this->validateSourcePath($sourcePath)) {
            $io->warning('Source path is not valid: ' . $sourcePath);

            return Command::FAILURE;
        }

        $actionClasses = $this->findActionClasses($sourcePath);

        if ($actionClasses->isEmpty()) {
            $io->warning('No Action classes found in: ' . $sourcePath);

            return Command::SUCCESS;
        }

        return $this->runTestsAndCheckCoverage($io, $actionClasses);
    }

    private function validateSourcePath(string $sourcePath): bool
    {
        return file_exists($sourcePath);
    }

    /**
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     */
    private function runTestsAndCheckCoverage(SymfonyStyle $io, Collection $actionClasses): int
    {
        $io->title('Running Tests for Action Classes');

        $coverageXmlPath = $this->generateCoverageReport();

        if ($coverageXmlPath === null) {
            $io->error('Coverage XML file was not generated.');

            return Command::FAILURE;
        }

        $coverage = $this->parseCoverageFromXml($coverageXmlPath, $actionClasses);

        unlink($coverageXmlPath);

        if ($this->hasLowCoverage($coverage, $actionClasses)) {
            $this->displayCoverageResults($io, $coverage, $actionClasses);

            return Command::FAILURE;
        }

        $io->newLine();
        $io->success('All Action classes have 100% code coverage!');

        return Command::SUCCESS;
    }

    private function generateCoverageReport(): ?string
    {
        $workingDirectory = getcwd();
        assert(is_string($workingDirectory));

        $coverageXmlPath = sys_get_temp_dir() . '/' . self::COVERAGE_FILE_PREFIX . uniqid() . self::FILE_EXTENSION_PHP . '.xml';

        $testCommand = sprintf(
            'cd %s && php %s vendor/bin/pest --configuration=%s/%s %s --coverage-clover=%s 2>&1',
            $workingDirectory,
            self::PHP_COVERAGE_OPTIONS,
            $workingDirectory,
            self::PHPUNIT_CONFIG_FILE,
            self::TESTS_DIRECTORY,
            $coverageXmlPath,
        );

        exec($testCommand, $output, $exitCode);

        if (!file_exists($coverageXmlPath)) {
            return null;
        }

        return $coverageXmlPath;
    }

    /**
     * @param array<string, float> $coverage
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     */
    private function hasLowCoverage(array $coverage, Collection $actionClasses): bool
    {
        return $actionClasses
            ->map(fn (string $filePath): float => $coverage[$filePath] ?? self::ZERO_COVERAGE)
            ->contains(fn (float $percent): bool => $percent < self::FULL_COVERAGE_THRESHOLD);
    }

    /**
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     * @return array<string, float>
     */
    private function parseCoverageFromXml(string $xmlPath, Collection $actionClasses): array
    {
        $xmlContent = file_get_contents($xmlPath);

        if ($xmlContent === false) {
            return [];
        }

        return $this->extractCoverageFromXml(new SimpleXMLElement($xmlContent), $actionClasses);
    }

    /**
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     * @return array<string, float>
     */
    private function extractCoverageFromXml(SimpleXMLElement $xml, Collection $actionClasses): array
    {
        $coverage = $this->initializeCoverage($actionClasses);

        $files = $xml->xpath(self::XPATH_FILE);

        if ($files === null) {
            return $coverage;
        }

        /** @var array<string, float> $result */
        $result = collect($files)
            ->filter(fn (SimpleXMLElement $file): bool => $this->isActionClassFile((string) $file['name'], $actionClasses))
            ->reduce(
                /**
                 * @param array<string, float> $coverage
                 * @return array<string, float>
                 */
                function (array $coverage, SimpleXMLElement $file): array {
                    $coverage[(string) $file['name']] = $this->calculateFileCoverage($file);

                    return $coverage;
                },
                $coverage,
            );

        return $result;
    }

    /**
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     * @return array<string, float>
     */
    private function initializeCoverage(Collection $actionClasses): array
    {
        /** @var array<string, float> $result */
        $result = $actionClasses->mapWithKeys(
            /**
             * @return array<string, float>
             */
            fn (string $filePath): array => [$filePath => self::ZERO_COVERAGE],
        )->toArray();

        return $result;
    }

    private function calculateFileCoverage(SimpleXMLElement $file): float
    {
        foreach ($file->class as $class) {
            $metrics = $class->metrics;

            if ($metrics === null) {
                continue;
            }

            $statements = (int) $metrics['statements'];
            $coveredStatements = (int) $metrics['coveredstatements'];

            if ($statements === 0) {
                return self::FULL_COVERAGE_THRESHOLD;
            }

            return $coveredStatements / $statements * 100;
        }

        return self::ZERO_COVERAGE;
    }

    /**
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     */
    private function isActionClassFile(string $fileName, Collection $actionClasses): bool
    {
        return $actionClasses->contains($fileName);
    }

    /**
     * @param array<string, float> $coverage
     * @param \Illuminate\Support\Collection<int, string> $actionClasses
     */
    private function displayCoverageResults(SymfonyStyle $io, array $coverage, Collection $actionClasses): void
    {
        $io->newLine();
        $io->error('The following Action classes have less than 100% coverage:');
        $io->newLine();

        $actionClasses
            ->filter(fn (string $filePath): bool => ($coverage[$filePath] ?? self::ZERO_COVERAGE) < self::FULL_COVERAGE_THRESHOLD)
            ->each(function (string $filePath) use ($io, $coverage): void {
                $className = basename($filePath, self::FILE_EXTENSION_PHP);
                $percent = $coverage[$filePath] ?? self::ZERO_COVERAGE;
                $io->writeln(sprintf('  - %s: %.2f%%', $className, $percent));
            });
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function findActionClasses(string $path): Collection
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
        );

        /** @var \Illuminate\Support\Collection<int, string> $result */
        $result = collect(iterator_to_array($iterator))
            ->map(fn (mixed $file): ?string => $this->processFile($file))
            ->filter(fn (?string $actionClass): bool => $actionClass !== null)
            ->values();

        return $result;
    }

    private function processFile(mixed $file): ?string
    {
        if (!$file instanceof SplFileInfo) {
            return null;
        }

        if (!$file->isFile() || !str_ends_with($file->getFilename(), self::FILE_EXTENSION_PHP)) {
            return null;
        }

        if (!$this->isActionClass($file->getPathname())) {
            return null;
        }

        $realPath = realpath($file->getPathname());

        return $realPath !== false ? $realPath : null;
    }

    private function isActionClass(string $filePath): bool
    {
        if (str_contains($filePath, self::EXCLUDED_DIRECTORY_COMMAND) || str_contains($filePath, self::EXCLUDED_DIRECTORY_PIPES)) {
            return false;
        }

        return str_contains((string) file_get_contents($filePath), self::ARCH_ACTION_INTERFACE);
    }

}
