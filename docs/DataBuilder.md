# Data Builder Pattern

Data Builder je návrhový vzor, který umožňuje snadné rozšiřování jakékoliv action o data transformace pomocí Laravel Pipeline patternu.

## Architektura

### Základní komponenty

1. **DataBuilder Interface** - Definuje kontrakt pro všechny data buildery
2. **BaseDataBuilder** - Abstraktní základní třída s implementací Laravel Pipeline
3. **DataBuilderFactory** - Factory pro vytváření builder instancí
4. **UsesDataBuilder Trait** - Trait pro snadné použití v action třídách

## Použití

### 1. Vytvoření nového Data Builderu

```php
<?php

namespace App\Actions\Product\DataBuilder;

use Pekral\Arch\Service\BaseDataBuilder;

final class ProductDataBuilder extends BaseDataBuilder
{
    public function getPipes(): array
    {
        return [
            NormalizeProductNamePipe::class,
            FormatProductPricePipe::class,
            ValidateProductCategoryPipe::class,
        ];
    }
}
```

### 2. Použití v Action třídě

#### Metoda 1: Použití Traitu (doporučeno)

```php
<?php

namespace App\Actions\Product;

use Pekral\Arch\Service\UsesDataBuilder;

final readonly class CreateProduct
{
    use UsesDataBuilder;

    public function __construct(
        private ProductService $productService,
    ) {
    }

    public function __invoke(array $data): Product
    {
        $transformedData = $this->transformDataWithBuilder($data, ProductDataBuilder::class);
        
        return $this->productService->create($transformedData);
    }
}
```

#### Metoda 2: Přímé použití Factory

```php
<?php

namespace App\Actions\Product;

use Pekral\Arch\Service\DataBuilderFactory;

final readonly class CreateProduct
{
    public function __construct(
        private ProductService $productService,
        private DataBuilderFactory $dataBuilderFactory,
    ) {
    }

    public function __invoke(array $data): Product
    {
        $dataBuilder = $this->dataBuilderFactory->create(ProductDataBuilder::class);
        $transformedData = $dataBuilder->build($data);
        
        return $this->productService->create($transformedData);
    }
}
```

## Vytvoření Pipe

```php
<?php

namespace App\Actions\Product\Pipes;

use Pekral\Arch\Examples\Acitons\User\Pipes\UserDataPipe;

final readonly class NormalizeProductNamePipe implements UserDataPipe
{
    public function handle(array $data, callable $next): array
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        return $next($data);
    }
}
```

## Pokročilé použití

### Vlastní Pipes pro specifickou Action

```php
public function __invoke(array $data): Product
{
    $customPipes = [
        NormalizeProductNamePipe::class,
        CustomValidationPipe::class,
    ];

    $transformedData = $this->transformData($data, ProductDataBuilder::class);
    
    // Nebo s custom pipes
    $dataBuilderFactory = app(DataBuilderFactory::class);
    $customBuilder = $dataBuilderFactory->createWithPipes(ProductDataBuilder::class, $customPipes);
    $transformedData = $customBuilder->build($data);
    
    return $this->productService->create($transformedData);
}
```

## Výhody

1. **Modulární** - Každý pipe má jednu zodpovědnost
2. **Rozšiřitelný** - Snadno se přidávají nové pipes
3. **Testovatelný** - Každý pipe lze testovat samostatně
4. **Znovupoužitelný** - Stejné pipes lze použít v různých builders
5. **Konfigurovatelný** - Builders lze registrovat podle kontextu
6. **Type-safe** - Všechny komponenty jsou správně typované

## Registrace Service Provideru

V `config/app.php` nebo v package service provideru:

```php
$this->app->register(DataBuilderServiceProvider::class);
```