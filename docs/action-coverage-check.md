# Action Coverage Check

Command-line tool that ensures every class implementing `ArchAction` has 100% test coverage.

## Overview

Running `bin/arch-coverage` registers the Symfony console command `arch:check-action-coverage`. The command looks up Action classes in the provided directory, executes Pest for `tests/Unit/Actions`, generates a temporary Clover coverage report and then verifies that each detected file reaches full coverage. Execution ends with a non-zero code whenever any file drops below the 100% threshold.

## Usage

```bash
php bin/arch-coverage examples/Actions
```

### Composer script

```bash
composer test:action-coverage
```

## How it works

1. **Class detection** – scans the `source` directory and records PHP files containing `implements ArchAction`. Any path with `/Command/` or `/Pipes/` is ignored.
2. **Test execution** – launches `vendor/bin/pest` with `phpunit.xml`, constrained to `tests/Unit/Actions` and writes coverage into a temporary Clover XML file.
3. **Coverage evaluation** – parses the XML via `SimpleXMLElement` and matches metrics with the detected files.
4. **Reporting** – prints a success message when all files pass; otherwise lists offending classes along with their percentage and returns a failure status.

## Example output

### Success
```
Running Tests for Action Classes
================================

 [OK] All Action classes have 100% code coverage!
```

### Insufficient coverage
```
Running Tests for Action Classes
================================

 [ERROR] The following Action classes have less than 100% coverage:

  - CreateUser: 96.34%
```

## Command parameters

| Name | Type | Description |
|------|------|-------------|
| `source` | argument | Absolute or relative directory containing Action classes |

## CI integration

```yaml
action-coverage:
  name: "🎯 Action Coverage (PHP 8.4)"
  runs-on: ubuntu-latest
  steps:
    - name: Check Action classes coverage
      run: bin/arch-coverage examples/Actions
```

The workflow fails if any Action class has coverage below 100%.

## Related links

- [`src/Command/CheckActionCoverageCommand.php`](../src/Command/CheckActionCoverageCommand.php)
- [`bin/arch-coverage`](../bin/arch-coverage)
- [`composer.json`](../composer.json)
