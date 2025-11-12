<?php

declare(strict_types = 1);

use Pekral\Arch\Command\CheckActionCoverageCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

test('configure sets command name and description', function (): void {
    $command = new CheckActionCoverageCommand();

    expect($command->getName())->toBe('arch:check-action-coverage')
        ->and($command->getDescription())->toContain('100%');
});

test('execute with valid source path and actions', function (): void {
    $tempDir = createTempDirWithAction();

    $application = new Application();
    $command = new CheckActionCoverageCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'source' => $tempDir,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain('Running Tests for Action Classes');

    removeTempDir($tempDir);
});

test('execute with invalid source path', function (): void {
    $application = new Application();
    $command = new CheckActionCoverageCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'source' => '/non/existent/path',
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain('Source path is not valid')
        ->and($commandTester->getStatusCode())->toBe(1);
});

test('execute with no action classes', function (): void {
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
    expect($output)->toContain('No Action classes found')
        ->and($commandTester->getStatusCode())->toBe(0);

    rmdir($tempDir);
});

test('execute with actions but no tests', function (): void {
    $tempDir = createTempDirWithAction();

    $application = new Application();
    $command = new CheckActionCoverageCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'source' => $tempDir,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain('Running Tests for Action Classes');

    removeTempDir($tempDir);
});

test('execute with filtered actions', function (): void {
    $tempDir = createTempDirWithCommandAndPipes();

    $application = new Application();
    $command = new CheckActionCoverageCommand();
    $application->add($command);

    $commandTester = new CommandTester($command);
    $commandTester->execute([
        'source' => $tempDir,
    ]);

    $output = $commandTester->getDisplay();
    expect($output)->toContain('No Action classes found');

    removeTempDir($tempDir);
});

function createTempDirWithAction(): string
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

function createTempDirWithCommandAndPipes(): string
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

function removeTempDir(string $dir): void
{
    removeDirRecursive($dir);
}

function removeDirRecursive(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = glob($dir . '/*');

    if ($files !== false) {
        foreach ($files as $file) {
            if (is_dir($file)) {
                removeDirRecursive($file);
            } else {
                unlink($file);
            }
        }
    }

    rmdir($dir);
}
