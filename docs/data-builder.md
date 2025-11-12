# DataBuilder

The `Pekral\Arch\DataBuilder\DataBuilder` trait provides a simple way to apply a sequence of transformations to any input data via the Laravel Pipeline. The result is always an array that is ready to be passed further (typically to a service or model manager).

## Feature overview

- `build()` validates that pipeline keys are consistent—either all numeric or all strings. The only allowed mix is the special `'*'` key that represents a global stage.
- All pipe definitions are resolved from the container through `app(Pipeline::class)` and receive the entire data array.
- Numeric keys (or `'*'`) are processed first, followed by string-keyed segments in their declared order.

## Basic usage

```php
<?php

namespace App\Actions\Product;

use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class CreateProduct
{
    use DataBuilder;

    public function __construct(private ProductService $productService)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): Product
    {
        $transformedData = $this->build($data, [
            NormalizeProductNamePipe::class,
            ValidateProductCategoryPipe::class,
        ]);

        return $this->productService->create($transformedData);
    }
}
```

## Named pipes and order

Pipes can be keyed with strings for clarity. Keys are only used to separate blocks and must share the same type across the definition.

```php
<?php

namespace App\Actions\User;

use Pekral\Arch\DataBuilder\DataBuilder;

final readonly class CreateUser
{
    use DataBuilder;

    public function __construct(private UserModelService $userModelService)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __invoke(array $data): User
    {
        $transformedData = $this->build($data, [
            '*' => NormalizeUserDataPipe::class, // runs first
            'email' => LowercaseEmailPipe::class,
            'name' => UcFirstNamePipe::class,
        ]);

        return $this->userModelService->create($transformedData);
    }
}
```

> **Note:** do not mix numeric and string keys. When you need both a global phase and named pipes, use the `'*'` key for the common stage and then rely solely on string keys.

## Defining a pipe

The package does not ship with its own contract—this is the minimal recommended interface:

```php
<?php

namespace App\Actions\User\Pipes;

interface BuilderPipe
{
    /**
     * @param array<string, mixed> $data
     * @param callable(array<string, mixed>): array<string, mixed> $next
     * @return array<string, mixed>
     */
    public function handle(array $data, callable $next): array;
}
```

An implementation only needs to adjust the data and forward it:

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

## `build` reference

```php
array<string, mixed> build(mixed $data, array $pipelines = [])
```

- **$data** – input dataset (array, DTO converted to array, etc.).
- **$pipelines** – list of pipe class names (`class-string`). When string keys are used the original order is respected.

The return value is always an associative array. If a pipe needs to produce a different structure temporarily, it should convert the result back to an array before returning.

## Examples

- `examples/Actions/User/CreateUser.php`
- `examples/Actions/User/Pipes/LowercaseEmailPipe.php`
- `examples/Actions/User/Pipes/UcFirstNamePipe.php`