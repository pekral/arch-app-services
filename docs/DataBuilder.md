# DataBuilder Trait

DataBuilder is a trait that enables easy data transformation for any action using the Laravel Pipeline pattern. It allows you to apply a series of transformations to data sequentially through defined pipes, supporting both general pipes and specific field pipes.

## Architecture

### Main Components

1. **DataBuilder Trait** - Trait with Laravel Pipeline implementation
2. **BuilderPipe Interface** - Interface for transformation pipes
3. **Pipe Implementation** - Concrete implementations of pipes for various transformations

### Pipe Types

- **General Pipes**: Applied to all data (integer keys or '*' key)
- **Specific Pipes**: Applied to specific fields (string keys)

## Usage

### Basic Usage in Action Class

```php
<?php

namespace App\Actions\Product;

use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class CreateProduct
{
    use DataBuilder;

    public function __construct(
        private ProductService $productService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): Product
    {
        $transformedData = $this->build($data, [
            // General pipes (applied to all data)
            NormalizeProductNamePipe::class,
            ValidateProductCategoryPipe::class,
            
            // Specific pipes (applied to specific fields)
            'name' => FormatProductNamePipe::class,
            'price' => FormatProductPricePipe::class,
        ]);
        
        return $this->productService->create($transformedData);
    }
}
```

### Simple Usage Example

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class CreateUser
{
    use DataBuilder;

    public function __construct(
        private UserModelService $userModelService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): User
    {
        $transformedData = $this->build($data, [
            'email' => NormalizeEmailPipe::class,
            'name' => ValidateUserDataPipe::class,
        ]);
        
        return $this->userModelService->create($transformedData);
    }
}
```

## Creating a Pipe

```php
<?php

namespace App\Actions\User\Pipes;

interface BuilderPipe
{
    /**
     * Transform user data.
     *
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): array<string, mixed> $next
     * @return array<string, mixed>
     */
    public function handle(array $data, callable $next): array;
}

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

final readonly class UcFirstNamePipe implements BuilderPipe
{
    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = str($data['name'])->lower()->ucfirst()->value();
        }

        return $next($data);
    }
}
```

## Method Reference

### `build(mixed $data, array $pipelines = []): array`

Transforms data using defined pipes via Laravel Pipeline.

**Parameters:**
- `mixed $data` - Input data to transform
- `array<string|int, class-string> $pipelines` - Array of pipe classes with keys

**Returns:**
- `array<string, mixed>` - Transformed data

**Pipe Processing Order:**
1. General pipes (integer keys or '*' key) are applied first
2. Specific pipes (string keys) are applied after general pipes

**Example:**
```php
$this->build($data, [
    // General pipes (applied to all data)
    0 => ValidateDataPipe::class,
    '*' => SanitizeDataPipe::class,
    
    // Specific pipes (applied to specific fields)
    'email' => LowercaseEmailPipe::class,
    'name' => UcFirstNamePipe::class,
]);
```

## Benefits

1. **Modular** - Each pipe has a single responsibility
2. **Extensible** - Easy to add new pipes
3. **Testable** - Each pipe can be tested independently
4. **Reusable** - Same pipes can be used in different contexts
5. **Type-safe** - All components are properly typed

## Examples

See `examples/Actions/User/CreateUser.php` and related pipe implementations for real-world usage examples.