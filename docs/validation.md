# Validation Layer

The package provides integrated validation through the DataValidator trait, which offers a simple interface for validating data using Laravel's validation system.

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

### Automatic Validation

```php
<?php

namespace App\Services;

use Pekral\Arch\Service\BaseModelService;
use App\Models\User;

final class UserService extends BaseModelService
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    protected function createModelManager(): UserModelManager
    {
        return new UserModelManager();
    }

    protected function createRepository(): UserRepository
    {
        return new UserRepository();
    }

    // Define validation rules for create operations
    protected function getCreateRules(): array
    {
        return [
            'email' => 'required|email|unique:users',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ];
    }

    // Define validation rules for update operations
    protected function getUpdateRules(): array
    {
        return [
            'email' => 'sometimes|email|unique:users,email,{id}',
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:6',
        ];
    }

    // Custom validation messages
    protected function getValidationMessages(): array
    {
        return [
            'email.email' => 'Email must be a valid email address.',
            'name.required' => 'Name is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    // Custom attribute names
    protected function getValidationAttributes(): array
    {
        return [
            'email' => 'Email Address',
            'name' => 'Name',
            'password' => 'Password',
        ];
    }
}
```

### Usage with Automatic Validation

```php
// Automatically uses getCreateRules()
$user = $userService->create([
    'email' => 'john@example.com',
    'name' => 'John Doe',
    'password' => 'password123',
]);

// Automatically uses getUpdateRules()
$userService->updateByParams(
    ['name' => 'Jane Doe'],
    ['id' => $user->id]
);
```

### Usage with Custom Rules

```php
// Custom validation rules for specific operation
$user = $userService->create($userData, [
    'email' => 'required|email|unique:users',
    'name' => 'required|string|max:255',
]);

$userService->updateByParams(
    $updateData,
    ['id' => $user->id],
    [
        'email' => 'sometimes|email',
        'name' => 'sometimes|string|max:255',
    ]
);
```

### Handling Validation Errors

```php
try {
    $user = $userService->create($invalidData);
} catch (\Illuminate\Validation\ValidationException $e) {
    $errors = $e->errors();
    
    foreach ($errors as $field => $messages) {
        echo "Error in field {$field}: " . implode(', ', $messages);
    }
}
```

## Configuration

Validation functions are configurable in `config/arch.php`:

```php
return [
    'default_items_per_page' => 15,
    'exceptions' => [
        'should_not_happen' => \RuntimeException::class,
    ],
];
```

## Advanced Features

### Custom Validation Rules

```php
// In service class
protected function getCreateRules(): array
{
    return [
        'email' => ['required', 'email', 'unique:users'],
        'name' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string', 'min:6', 'confirmed'],
    ];
}
```

### Conditional Validation

```php
protected function getUpdateRules(): array
{
    return [
        'email' => 'sometimes|email|unique:users,email,{id}',
        'name' => 'sometimes|string|max:255',
        'password' => 'sometimes|string|min:6|confirmed',
        'role' => 'sometimes|in:admin,user,moderator',
    ];
}
```

### Validation Messages in Different Languages

```php
protected function getValidationMessages(): array
{
    return [
        'email.required' => __('validation.required', ['attribute' => 'email']),
        'email.email' => __('validation.email', ['attribute' => 'email']),
        'name.required' => __('validation.required', ['attribute' => 'name']),
    ];
}
```
