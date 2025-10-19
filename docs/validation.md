# Data Validation Layer

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
