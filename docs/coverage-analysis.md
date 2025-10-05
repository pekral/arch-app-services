# Coverage Analysis Script

Simple script for checking code coverage with link to PHPUnit HTML report.

## Features

- ✅ **Simple check** - Shows warning only if coverage is not 100%
- ✅ **PHPUnit HTML report** - Link to professional PHPUnit HTML report
- ✅ **Minimal output** - Only basic information without complex analysis
- ✅ **Cross-platform** - Works on macOS, Linux and Windows

## Usage

### Basic coverage analysis
```bash
composer coverage
```

### Detailed coverage analysis
```bash
composer coverage:detailed
```

### Open PHPUnit HTML report in browser
```bash
composer coverage:open
```

## Output

Script outputs:
- ⚠️ Warning if coverage is not 100%
- 📄 Link to PHPUnit HTML report
- ✅ Confirmation if coverage is 100%

## PHPUnit HTML Report

PHPUnit HTML report contains:
- 📊 Professional dashboard with overview
- 🎨 Color indicators for coverage at line level
- 📁 Detailed file and class browsing
- 📍 Highlighting of uncovered lines
- 📱 Responsive design
- 🔍 Interactive navigation

## Output Examples

### If coverage is not 100%:
```
⚠️  Codebase is not 100% covered by tests: 57.43%
📄 HTML report: file:///Users/petrkral/Projects/arch-app-services/coverage-html/index.html
```

### If coverage is 100%:
```
✅ Codebase is 100% covered by tests: 100.00%
```

## Requirements

- PHP 8.4+
- PHPUnit with PCOV extension
- Composer

## Files

- `check-coverage.php` - Main analysis script
- `coverage-html/` - Folder with PHPUnit HTML report
- `coverage-html/index.html` - Main HTML report page
- `coverage.xml` - PHPUnit coverage data (Clover format)
