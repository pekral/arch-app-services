<?php

declare(strict_types = 1);

namespace Pekral\Arch\Tests\Unit\Command;

use Pekral\Arch\Command\CheckActionCoverageCommand;
use Pekral\Arch\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckActionCoverageCommandTest extends TestCase
{

    public function testConfigure(): void
    {
        $command = new CheckActionCoverageCommand();

        $this->assertSame('arch:check-action-coverage', $command->getName());
        $this->assertStringContainsString('100%', $command->getDescription());
    }

    public function testExecuteWithValidSourcePathAndActions(): void
    {
        $tempDir = $this->createTempDirWithAction();

        $application = new Application();
        $command = new CheckActionCoverageCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'source' => $tempDir,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running Tests for Action Classes', $output);

        $this->removeTempDir($tempDir);
    }

    public function testExecuteWithInvalidSourcePath(): void
    {
        $application = new Application();
        $command = new CheckActionCoverageCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'source' => '/non/existent/path',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Source path is not valid', $output);
        $this->assertSame(1, $commandTester->getStatusCode());
    }

    public function testExecuteWithNoActionClasses(): void
    {
        $tempDir = sys_get_temp_dir() . '/test-no-actions-' . uniqid();
        mkdir($tempDir);

        $application = new Application();
        $command = new CheckActionCoverageCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'source' => $tempDir,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No Action classes found', $output);
        $this->assertSame(0, $commandTester->getStatusCode());

        rmdir($tempDir);
    }

    public function testExecuteWithActionsButNoTests(): void
    {
        $tempDir = $this->createTempDirWithAction();

        $application = new Application();
        $command = new CheckActionCoverageCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'source' => $tempDir,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running Tests for Action Classes', $output);

        $this->removeTempDir($tempDir);
    }

    public function testExecuteWithFilteredActions(): void
    {
        $tempDir = $this->createTempDirWithCommandAndPipes();

        $application = new Application();
        $command = new CheckActionCoverageCommand();
        $application->add($command);

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'source' => $tempDir,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No Action classes found', $output);

        $this->removeTempDir($tempDir);
    }

    private function createTempDirWithAction(): string
    {
        $tempDir = sys_get_temp_dir() . '/test-actions-' . uniqid();
        mkdir($tempDir);

        $actionContent = <<<'PHP'
<?php
namespace Test;
use Pekral\Arch\Action\ArchAction;
final class TestAction implements ArchAction {
    public function handle(): void {}
}
PHP;

        file_put_contents($tempDir . '/TestAction.php', $actionContent);

        return $tempDir;
    }

    private function createTempDirWithCommandAndPipes(): string
    {
        $tempDir = sys_get_temp_dir() . '/test-filtered-' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/Command');
        mkdir($tempDir . '/Pipes');

        $commandContent = <<<'PHP'
<?php
namespace Test\Command;
use Pekral\Arch\Action\ArchAction;
final class TestCommand implements ArchAction {
    public function handle(): void {}
}
PHP;

        $pipeContent = <<<'PHP'
<?php
namespace Test\Pipes;
use Pekral\Arch\Action\ArchAction;
final class TestPipe implements ArchAction {
    public function handle(): void {}
}
PHP;

        file_put_contents($tempDir . '/Command/TestCommand.php', $commandContent);
        file_put_contents($tempDir . '/Pipes/TestPipe.php', $pipeContent);

        return $tempDir;
    }

    private function removeTempDir(string $dir): void
    {
        $this->removeDirRecursive($dir);
    }

    private function removeDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*');

        if ($files !== false) {
            foreach ($files as $file) {
                if (is_dir($file)) {
                    $this->removeDirRecursive($file);
                } else {
                    unlink($file);
                }
            }
        }

        rmdir($dir);
    }

}
