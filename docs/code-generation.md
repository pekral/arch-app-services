# Code Generation

The package provides Artisan commands for generating boilerplate code to quickly scaffold architecture components.

## Available Commands

| Command | Description | Generated Location |
|---------|-------------|-------------------|
| `make:arch-action` | Create a new Action class | `app/Actions/` |
| `make:arch-service` | Create a complete service stack | `app/Services/{Model}/` |
| `make:arch-dto` | Create a new DTO class | `app/DTO/` |
| `make:arch-validation-rules` | Create a new ValidationRules class | `app/Rules/` |

## Action Class

Create a new Action class that implements the `ArchAction` interface.

### Usage

```bash
php artisan make:arch-action {name}
```

### Examples

```bash
# Create action in app/Actions/
php artisan make:arch-action CreateUserAction

# Create action in subdirectory app/Actions/User/
php artisan make:arch-action User/CreateUserAction
```

### Generated Code

```php
<?php

declare(strict_types = 1);

namespace App\Actions\User;

use Pekral\Arch\Action\ArchAction;

final readonly class CreateUserAction implements ArchAction
{

    public function __construct()
    {
    }

    public function execute(): void
    {
        // TODO: Implement action logic
    }

}
```

## Service Stack

Create a complete service stack including Service, Repository, and ModelManager for a model.

### Usage

```bash
php artisan make:arch-service {model} [options]
```

### Options

| Option | Description |
|--------|-------------|
| `--no-repository` | Skip repository generation |
| `--no-model-manager` | Skip model manager generation |
| `--force` | Overwrite existing files |

### Examples

```bash
# Create full stack (Service, Repository, ModelManager)
php artisan make:arch-service "App\Models\User"

# Create only Service and Repository (no ModelManager)
php artisan make:arch-service "App\Models\User" --no-model-manager

# Create only Service and ModelManager (no Repository)
php artisan make:arch-service "App\Models\User" --no-repository

# Create only Service (minimal)
php artisan make:arch-service "App\Models\User" --no-repository --no-model-manager

# Overwrite existing files
php artisan make:arch-service "App\Models\User" --force
```

### Generated Files

For `php artisan make:arch-service "App\Models\User"`:

**app/Services/User/UserModelManager.php**
```php
<?php

declare(strict_types = 1);

namespace App\Services\User;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use App\Models\User;

/**
 * @extends BaseModelManager<User>
 */
final class UserModelManager extends BaseModelManager
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
```

**app/Services/User/UserRepository.php**
```php
<?php

declare(strict_types = 1);

namespace App\Services\User;

use Pekral\Arch\Repository\Mysql\BaseRepository;
use App\Models\User;

/**
 * @extends BaseRepository<User>
 */
final class UserRepository extends BaseRepository
{

    protected function getModelClassName(): string
    {
        return User::class;
    }

}
```

**app/Services/User/UserModelService.php**
```php
<?php

declare(strict_types = 1);

namespace App\Services\User;

use Pekral\Arch\ModelManager\Mysql\BaseModelManager;
use Pekral\Arch\Repository\Mysql\BaseRepository;
use Pekral\Arch\Service\BaseModelService;
use App\Models\User;

/**
 * @extends BaseModelService<User>
 */
final readonly class UserModelService extends BaseModelService
{

    public function __construct(
        private UserModelManager $userModelManager,
        private UserRepository $userRepository,
    ) {
    }

    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function getModelManager(): BaseModelManager
    {
        return $this->userModelManager;
    }

    protected function getRepository(): BaseRepository
    {
        return $this->userRepository;
    }

}
```

## DTO Class

Create a new Data Transfer Object class that extends `DataTransferObject`.

### Usage

```bash
php artisan make:arch-dto {name}
```

### Examples

```bash
# Create DTO in app/DTO/
php artisan make:arch-dto CreateUserDTO

# Create DTO in subdirectory app/DTO/User/
php artisan make:arch-dto User/CreateUserDTO
```

### Generated Code

```php
<?php

declare(strict_types = 1);

namespace App\DTO\User;

use Pekral\Arch\DTO\DataTransferObject;

final class CreateUserDTO extends DataTransferObject
{

    public function __construct(
        // TODO: Define DTO properties with validation attributes
        // #[Required]
        // public string $property,
    ) {
    }

}
```

### Customization

After generation, add your properties with validation attributes:

```php
<?php

declare(strict_types = 1);

namespace App\DTO\User;

use Pekral\Arch\DTO\DataTransferObject;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;

final class CreateUserDTO extends DataTransferObject
{

    public function __construct(
        #[Email, Required]
        public string $email,
        #[Max(255), Required]
        public string $name,
    ) {
    }

}
```

For more information about DTOs, see [Data Validation Documentation](validation.md#data-transfer-objects-dto).

## ValidationRules Class

Create a new ValidationRules class that implements the `ValidationRules` interface.

### Usage

```bash
php artisan make:arch-validation-rules {name}
```

### Examples

```bash
# Create validation rules in app/Rules/
php artisan make:arch-validation-rules UserValidationRules

# Create validation rules in subdirectory app/Rules/User/
php artisan make:arch-validation-rules User/UserValidationRules
```

### Generated Code

```php
<?php

declare(strict_types = 1);

namespace App\Rules\User;

use Pekral\Arch\DataValidation\ValidationRules;

final class UserValidationRules implements ValidationRules
{

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            // TODO: Define validation rules
            // 'field_name' => self::fieldNameRules(),
        ];
    }

}
```

### Customization

After generation, add your validation rules:

```php
<?php

declare(strict_types = 1);

namespace App\Rules\User;

use Pekral\Arch\DataValidation\ValidationRules;

final class UserValidationRules implements ValidationRules
{

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => self::emailRules(),
            'name' => self::nameRules(),
            'phone' => self::phoneRules(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public static function emailRules(): array
    {
        return ['required', 'email'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function nameRules(): array
    {
        return ['required', 'max:255'];
    }

    /**
     * @return array<int, mixed>
     */
    public static function phoneRules(): array
    {
        return ['nullable', new CzechPhoneRule()];
    }

}
```

For more information about ValidationRules, see [Data Validation Documentation](validation.md#validationrules-interface).

## Customizing Stubs

You can publish and customize the stub files used for code generation:

```bash
php artisan vendor:publish --tag="arch-stubs"
```

This will publish the stub files to `stubs/arch/` directory in your application:

```
stubs/arch/
├── action.stub
├── dto.stub
├── model-manager.stub
├── repository.stub
├── service.stub
├── service-minimal.stub
├── service-model-manager-only.stub
├── service-repository-only.stub
└── validation-rules.stub
```

Modify these files to match your project's coding style and conventions.

## Best Practices

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Action | `{Verb}{Entity}Action` | `CreateUserAction`, `UpdateOrderAction` |
| Service | `{Entity}ModelService` | `UserModelService`, `OrderModelService` |
| Repository | `{Entity}Repository` | `UserRepository`, `OrderRepository` |
| ModelManager | `{Entity}ModelManager` | `UserModelManager`, `OrderModelManager` |
| DTO | `{Verb}{Entity}DTO` | `CreateUserDTO`, `UpdateOrderDTO` |
| ValidationRules | `{Entity}ValidationRules` | `UserValidationRules`, `OrderValidationRules` |

### Directory Structure

Recommended directory structure:

```
app/
├── Actions/
│   ├── User/
│   │   ├── CreateUserAction.php
│   │   ├── UpdateUserAction.php
│   │   └── DeleteUserAction.php
│   └── Order/
│       └── CreateOrderAction.php
├── DTO/
│   ├── User/
│   │   ├── CreateUserDTO.php
│   │   └── UpdateUserDTO.php
│   └── Order/
│       └── CreateOrderDTO.php
├── Rules/
│   ├── User/
│   │   └── UserValidationRules.php
│   └── Order/
│       └── OrderValidationRules.php
└── Services/
    ├── User/
    │   ├── UserModelManager.php
    │   ├── UserModelService.php
    │   └── UserRepository.php
    └── Order/
        ├── OrderModelManager.php
        ├── OrderModelService.php
        └── OrderRepository.php
```
