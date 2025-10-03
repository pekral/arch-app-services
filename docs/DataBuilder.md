# DataBuilder Class

DataBuilder is a service class that enables easy data transformation for any action using the Laravel Pipeline pattern. It allows you to apply a series of transformations to data sequentially through defined pipes.

## Architecture

### Main Components

1. **DataBuilder Class** - Final readonly class with Laravel Pipeline implementation
2. **BuilderPipe Interface** - Interface for transformation pipes
3. **Pipe Implementation** - Concrete implementations of pipes for various transformations

## Usage

### Basic Usage in Action Class

```php
<?php

namespace App\Actions\Product;

use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class CreateProduct
{
    public function __construct(
        private ProductService $productService,
        private DataBuilder $dataBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): Product
    {
        $pipes = [
            NormalizeProductNamePipe::class,
            FormatProductPricePipe::class,
            ValidateProductCategoryPipe::class,
        ];
        
        $transformedData = $this->dataBuilder->build($data, $pipes);
        
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
    public function __construct(
        private UserModelService $userModelService,
        private DataBuilder $dataBuilder,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): User
    {
        $pipes = [
            NormalizeEmailPipe::class,
            ValidateUserDataPipe::class,
        ];

        $transformedData = $this->dataBuilder->build($data, $pipes);
        
        return $this->userModelService->create($transformedData);
    }
}
```

## Creating a Pipe

```php
<?php

namespace App\Actions\Product\Pipes;

use Pekral\Arch\Examples\Actions\User\Pipes\BuilderPipe;

final readonly class NormalizeProductNamePipe implements BuilderPipe
{
    /**
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): array<string, mixed> $next
     * @return array<string, mixed>
     */
    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        return $next($data);
    }
}
```

## Method Reference

### `build(array $data, array $pipes): array`

Transforms data using defined pipes via Laravel Pipeline.

**Parameters:**
- `array<string, mixed> $data` - Input data to transform
- `array<class-string> $pipes` - Array of pipe classes implementing handle method

**Returns:**
- `array<string, mixed>` - Transformed data

## Benefits

1. **Modular** - Each pipe has a single responsibility
2. **Extensible** - Easy to add new pipes
3. **Testable** - Each pipe can be tested independently
4. **Reusable** - Same pipes can be used in different contexts
5. **Type-safe** - All components are properly typed

## Examples

See `examples/Actions/User/CreateUser.php` and related pipe implementations for real-world usage examples.