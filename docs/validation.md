# Data Validation Layer

The package provides integrated validation through:
1. **DataValidator trait** - Simple interface for validating data using Laravel's validation system
2. **Data Transfer Objects (DTO)** - Type-safe data objects with attribute-based validation using Spatie Laravel Data

## Basic Usage

### Using DataValidator Trait

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataValidation\DataValidator;

final readonly class ValidateUserData
{
    use DataValidator;
    
    public function validateUser(array $data): array
    {
        return $this->validate($data, [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'name.required' => 'Name is required.',
        ]);
    }
}
```

### Using in Actions

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataValidation\DataValidator;

final readonly class CreateUser
{
    use DataValidator;
    
    public function execute(array $data): User
    {
        $this->validate($data, [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
        ], []);
        
        return $this->userModelService->create($data);
    }
}
```

### Validation Exception Handling

```php
try {
    $validatedData = $this->validate($data, $rules, $messages);
} catch (\Illuminate\Validation\ValidationException $e) {
    $errors = $e->errors();
    
    foreach ($errors as $field => $messages) {
        echo "Error in field {$field}: " . implode(', ', $messages);
    }
}
```

## Method Reference

### `validate(mixed $data, array $rules, array $messages): array`

Validates data using Laravel's validation system.

**Parameters:**
- `mixed $data` - Data to validate
- `array<string, mixed> $rules` - Validation rules
- `array<string, mixed> $messages` - Custom validation messages

**Returns:**
- `array<string, mixed>` - Validated data

**Throws:**
- `\Illuminate\Validation\ValidationException` - When validation fails

## Advanced Usage

### Combining with DataBuilder

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;
use Pekral\Arch\DataValidation\DataValidator;

final readonly class CreateUser
{
    use DataBuilder;
    use DataValidator;

    public function __construct(
        private UserModelService $userModelService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(array $data): User
    {
        // Validate input data
        $this->validate($data, [
            'email' => 'required|email',
            'name' => 'required|string',
        ], []);
        
        // Transform data using pipeline
        $normalizedData = $this->build($data, [
            'email' => LowercaseEmailPipe::class,
            'name' => UcFirstNamePipe::class,
        ]);
        
        return $this->userModelService->create($normalizedData);
    }
}
```

### Custom Validation Rules

```php
// In action class
public function execute(array $data): User
{
    $this->validate($data, [
        'email' => ['required', 'email', 'unique:users'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:6', 'confirmed'],
    ], [
        'email.email' => 'Email must be a valid email address.',
        'name.required' => 'Name is required.',
        'password.min' => 'Password must be at least 6 characters.',
    ]);
    
    return $this->userModelService->create($data);
}
```

### Conditional Validation

```php
public function execute(array $data): User
{
    $rules = [
        'email' => 'sometimes|email|unique:users',
        'name' => 'sometimes|string|max:255',
        'password' => 'sometimes|string|min:6|confirmed',
        'role' => 'sometimes|in:admin,user,moderator',
    ];
    
    $this->validate($data, $rules, []);
    
    return $this->userModelService->create($data);
}
```

### Validation Messages in Different Languages

```php
public function execute(array $data): User
{
    $messages = [
        'email.required' => __('validation.required', ['attribute' => 'email']),
        'email.email' => __('validation.email', ['attribute' => 'email']),
        'name.required' => __('validation.required', ['attribute' => 'name']),
    ];
    
    $this->validate($data, [
        'email' => 'required|email',
        'name' => 'required|string',
    ], $messages);
    
    return $this->userModelService->create($data);
}
```

## Data Transfer Objects (DTO)

DTOs provide type-safe data objects with attribute-based validation using Spatie Laravel Data.

### Basic DTO Usage

```php
<?php

namespace App\DTO;

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
        #[Max(20)]
        public ?string $phone = null,
    ) {
    }
}
```

### Creating DTO from Data

```php
// From array (no validation)
$dto = CreateUserDTO::from([
    'email' => 'user@example.com',
    'name' => 'John Doe',
]);

// With validation
$dto = CreateUserDTO::validateAndCreate([
    'email' => 'user@example.com',
    'name' => 'John Doe',
]);

// From request
$dto = CreateUserDTO::from($request->all());

// Direct construction
$dto = new CreateUserDTO(
    email: 'user@example.com',
    name: 'John Doe',
);
```

### DTO Methods

```php
// Convert to array
$array = $dto->toArray();

// JSON serialization
$json = json_encode($dto);
```

### Available Validation Attributes

Spatie Laravel Data provides 90+ validation attributes that map to Laravel validation rules:

| Attribute | Laravel Rule |
|-----------|--------------|
| `#[Email]` | `'email'` |
| `#[Required]` | `'required'` |
| `#[Max(255)]` | `'max:255'` |
| `#[Min(1)]` | `'min:1'` |
| `#[Nullable]` | `'nullable'` |
| `#[Unique('users', 'email')]` | `'unique:users,email'` |
| `#[Exists('roles', 'id')]` | `'exists:roles,id'` |
| `#[In(['admin', 'user'])]` | `'in:admin,user'` |
| `#[Regex('/^[A-Z]+$/')]` | `'regex:/^[A-Z]+$/'` |
| `#[Date]` | `'date'` |
| `#[Numeric]` | `'numeric'` |
| `#[Url]` | `'url'` |
| `#[Uuid]` | `'uuid'` |

### Custom Validation Rules in DTO

You can use custom Laravel validation rules in DTOs using the `#[Rule()]` attribute:

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CzechPhoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        assert($attribute !== '');

        if (!preg_match('/^\+420\d{9}$/', $value)) {
            $fail('The phone number must be in format +420XXXXXXXXX.');
        }
    }
}
```

Use in DTO:

```php
<?php

namespace App\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use App\Rules\CzechPhoneRule;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Rule;

final class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[Email, Required]
        public string $email,
        #[Nullable, Rule(new CzechPhoneRule())]
        public ?string $phone = null,
    ) {
    }
}
```

### Using Laravel Rule Objects

```php
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Rule as RuleAttribute;

final class CreateUserDTO extends DataTransferObject
{
    public function __construct(
        #[RuleAttribute(Rule::in(['admin', 'user', 'guest']))]
        public string $role,
        
        #[RuleAttribute(Rule::unique('users', 'email')->ignore($userId))]
        public string $email,
    ) {
    }
}
```

### Sharing Validation Rules Between Request and DTO

Create centralized validation rules that can be used in both Laravel Requests and DTOs by implementing the `ValidationRules` interface:

```php
<?php

namespace App\Rules;

use Pekral\Arch\DataValidation\ValidationRules;

final class UserValidationRules implements ValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'name' => ['required', 'max:255'],
            'phone' => ['nullable', new CzechPhoneRule()],
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

**Using in Laravel Request:**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UserValidationRules;

final class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return UserValidationRules::rules();
    }
}
```

**Using in DTO:**

```php
<?php

namespace App\DTO;

use Pekral\Arch\DTO\DataTransferObject;
use App\Rules\UserValidationRules;
use Spatie\LaravelData\Attributes\Validation\Rule;

final class CreateUserWithSharedRulesDTO extends DataTransferObject
{
    public function __construct(
        #[Rule(UserValidationRules::emailRules())]
        public string $email,
        #[Rule(UserValidationRules::nameRules())]
        public string $name,
        #[Rule(UserValidationRules::phoneRules())]
        public ?string $phone = null,
    ) {
    }
}
```

### DTO Validation Exception Handling

```php
use Illuminate\Validation\ValidationException;

try {
    $dto = CreateUserDTO::validateAndCreate($data);
} catch (ValidationException $e) {
    $errors = $e->errors();
    
    foreach ($errors as $field => $messages) {
        echo "Error in field {$field}: " . implode(', ', $messages);
    }
}
```

### Using DTO in Actions

```php
<?php

namespace App\Actions\User;

use App\DTO\CreateUserDTO;
use App\Services\UserModelService;
use App\Models\User;

final readonly class CreateUserWithDTO
{
    public function __construct(
        private UserModelService $userModelService,
    ) {
    }

    public function execute(CreateUserDTO $dto): User
    {
        return $this->userModelService->create($dto->toArray());
    }
}

// Usage in controller
public function store(Request $request, CreateUserWithDTO $action)
{
    $dto = CreateUserDTO::validateAndCreate($request->all());
    $user = $action->execute($dto);
    
    return response()->json($user, 201);
}
```

## ValidationRules Interface

The package provides a `ValidationRules` interface for creating centralized validation rule classes.

### Interface Definition

```php
<?php

namespace Pekral\Arch\DataValidation;

interface ValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array;
}
```

### PHPStan Rules for ValidationRules

Two PHPStan rules enforce proper usage of ValidationRules classes:

#### ValidationRulesMethodNamingRule

Ensures ValidationRules classes:
- ❌ Must not have a constructor
- ❌ All methods must be static
- ❌ All methods must end with `Rules` suffix

```php
// ❌ Violations
final class UserValidationRules implements ValidationRules
{
    public function __construct() {}  // Constructor not allowed
    public function email(): array {} // Must be static
    public static function getEmail(): array {} // Must end with "Rules"
}

// ✅ Correct
final class UserValidationRules implements ValidationRules
{
    public static function rules(): array { ... }
    public static function emailRules(): array { ... }
    public static function phoneRules(): array { ... }
}
```

#### ValidationRulesNoInstantiationRule

Prevents instantiation of ValidationRules classes:

```php
// ❌ Violation
$rules = new UserValidationRules();

// ✅ Correct - use static methods
$rules = UserValidationRules::rules();
$emailRules = UserValidationRules::emailRules();
```

For more details on PHPStan rules, see [PHPStan Rules Documentation](phpstan-rules.md).
