# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**pekral/arch-app-services** is a Laravel package providing clean architectural abstractions for building scalable applications. It implements Action, Repository, Model Manager, and Service patterns with strong PHPStan enforcement.

**Package is in active development** - API may change in future versions.

## Development Commands

### Testing
```bash
# Run tests with 100% coverage requirement
composer test:coverage

# Run tests (without coverage)
vendor/bin/pest
```

### Code Quality
```bash
# Full quality check (normalizer, phpcs, pint, rector, phpstan, security, tests)
composer check

# Run PHPStan analysis
composer analyse

# Fix all code style issues
composer fix

# Individual checks/fixes
composer pint-check     # Laravel Pint style check
composer pint-fix       # Laravel Pint style fix
composer phpcs-check    # PHP CodeSniffer check
composer phpcs-fix      # PHP CodeSniffer fix
composer rector-check   # Rector dry-run
composer rector-fix     # Rector fixes
```

### Security
```bash
composer security-audit
```

## Core Architecture

### Layer Separation

1. **Actions** (`examples/Actions/`)
   - Single-purpose classes handling specific business operations
   - Must implement `ArchAction` interface or extend action base classes
   - **CANNOT** use direct Eloquent methods (`save()`, `create()`, `delete()`) - enforced by PHPStan
   - **CANNOT** use direct database queries (`where()`, `find()`, `get()`) - enforced by PHPStan
   - **MUST** use Repository for reads, ModelManager/Service for writes
   - Can use `DataBuilder` trait for data transformation via pipelines
   - Can use `DataValidator` trait for validation
   - Can use `ActionLogger` trait for execution logging

2. **Services** (`src/Service/`, `examples/Services/`)
   - Extend `BaseModelService<TModel>`
   - Combine Repository (reads) + ModelManager (writes)
   - Provide complete CRUD operations with validation
   - Must end with `ModelService` suffix - enforced by PHPStan

3. **Repositories** (`src/Repository/`)
   - Handle read operations only
   - Extend `BaseRepository<TModel>` (MySQL) or DynamoDB equivalent
   - Use `CacheableRepository` trait for automatic caching
   - Provide fluent query interface via `query()` method
   - Support filtering, pagination, eager loading

4. **Model Managers** (`src/ModelManager/`)
   - Handle write operations only (create, update, delete)
   - Extend `BaseModelManager<TModel>`
   - Support bulk operations with duplicate handling
   - Only layer allowed to persist data - enforced by PHPStan

5. **Data Transformation**
   - `DataBuilder` trait - pipeline-based transformation using Laravel Pipeline
   - `BuilderPipe` interface - individual transformation steps
   - Process order: general pipes → specific field pipes

6. **Validation**
   - `DataValidator` trait - Laravel validation integration
   - Used in Actions for input validation

### Database Support

- **MySQL/MariaDB**: Full support (primary target)
- **DynamoDB**: Experimental support (some limitations)

## PHPStan Architecture Enforcement

The package includes custom PHPStan rules at max level:

1. **NoEloquentStorageMethodsInActionsRule** - Actions cannot call `save()`, `create()`, `update()`, `delete()`, etc.
2. **NoDirectDatabaseQueriesInActionsRule** - Actions cannot call `where()`, `find()`, `get()`, etc.
3. **OnlyModelManagersCanPersistDataRule** - Only ModelManager/ModelService can persist data
4. **NoLaravelHelpersForActionsRule** - Actions must use constructor injection, not `app()`, `resolve()`, `make()`
5. **ServiceNamingConventionRule** - Services extending `BaseModelService` must end with `ModelService`
6. **ActionExecuteMethodRule** - Actions must have proper execute/handle methods

These rules maintain clean architecture boundaries.

## Code Standards

### PHP Version
- **PHP 8.4+** (check `composer.json` for actual version)
- Use modern PHP features (typed properties, match expressions, etc.)

### Class Conventions
- All classes marked as `final` unless designed for inheritance
- Use `readonly` for immutable classes
- Classes in PascalCase, methods/properties in camelCase
- Method names: verbs + description (`createUser`, `getOrderById`)
- Boolean methods: use `is`, `has`, `can` prefixes (`isActive`, `hasErrors`)

### PHPDoc Requirements
- Full type annotations with generics support
- Example: `@extends BaseRepository<User>`, `@param array<string, mixed>`
- Document return types: `@return array{total_processed: int, created: int}`

### Type Hints
- **Never** use nullable array (`?array`) - use `array` or specific type
- Use short nullable: `?string` not `string|null`
- Always specify `void` return types
- Use `Collection|array` for flexible parameters

### Code Style
- Add blank lines between statements (except equivalent single-line operations)
- No extra empty lines between `{}` brackets
- Extract repeated code into reusable functions (DRY principle)
- Meaningful variable names (not `$a`, `$tmp`)

## Testing with Pest

### Framework
- **Pest 4.x** (check `composer.json` for actual version)
- 100% code coverage required
- Tests must be marked as `final`

### Test Structure
- Follow existing test patterns in `tests/` directory
- Use data providers (via argument, not phpdoc)
- Never create test class properties - prefer local variables
- Mocking only for third-party services using `Mockery::class`
- Assert only values from tested class/method
- Test data in English

### Prohibited
- **Never** test abstract classes
- **Never** test protected/private methods or properties
- **Never** modify logic outside tests
- **Never** write tests for non-existing functionality

### Running Tests
```bash
# Run all tests with coverage
composer test:coverage

# Run specific test file
vendor/bin/pest tests/Unit/Actions/User/CreateUserTest.php
```

## Configuration

### Published Config
```bash
php artisan vendor:publish --tag="arch-config"
```

### Key Settings (`config/arch.php`)
- `default_items_per_page`: Pagination default (15)
- `action_logging.enabled`: Enable action execution logging
- `action_logging.channel`: Log channel for actions
- `repository_cache.enabled`: Enable repository caching
- `repository_cache.ttl`: Cache TTL in seconds (3600)
- `repository_cache.prefix`: Cache key prefix

### Environment Variables
```bash
ARCH_ACTION_LOG_CHANNEL=actions
ARCH_ACTION_LOGGING_ENABLED=true
ARCH_REPOSITORY_CACHE_ENABLED=true
ARCH_REPOSITORY_CACHE_TTL=3600
ARCH_REPOSITORY_CACHE_PREFIX=arch_repo
```

## Key Patterns

### Action Pattern
```php
final readonly class CreateUser implements ArchAction
{
    use DataBuilder;
    use DataValidator;

    public function __construct(
        private UserModelService $userModelService,
    ) {}

    public function execute(array $data): User
    {
        $this->validate($data, [...]);
        $transformed = $this->build($data, [
            'email' => LowercaseEmailPipe::class,
        ]);
        return $this->userModelService->create($transformed);
    }
}
```

### Repository with Caching
```php
final class UserRepository extends BaseRepository
{
    use CacheableRepository;

    protected function getModelClassName(): string
    {
        return User::class;
    }
}

// Usage in Actions
$user = $repository->cache()->getOneByParams(['email' => 'test@example.com']);
```

### Service Pattern
```php
final readonly class UserModelService extends BaseModelService
{
    public function __construct(
        private UserModelManager $userModelManager,
        private UserRepository $userRepository,
    ) {}

    protected function getModelClass(): string { return User::class; }
    protected function getModelManager(): BaseModelManager { return $this->userModelManager; }
    protected function getRepository(): BaseRepository { return $this->userRepository; }
}
```

### Data Builder Pipe
```php
final readonly class LowercaseEmailPipe implements BuilderPipe
{
    public function handle(array $data, callable $next): array
    {
        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
        return $next($data);
    }
}
```

## Important Files

- `phpstan.neon` - PHPStan configuration with custom architecture rules
- `composer.json` - Scripts for all development tasks
- `config/arch.php` - Package configuration
- `examples/` - Reference implementations
- `phpstan/Rules/` - Custom PHPStan rules enforcing architecture

## Notes

- Always preserve existing code structure
- Never apologize in code or messages
- Verify information before presenting
- Don't invent changes beyond explicit requests
- Follow DRY principle
- Use enum values in CAPITAL_CASE
