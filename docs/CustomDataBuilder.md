# Custom DataBuilder - Obecnější řešení

Toto řešení umožňuje definovat vlastní DataBuilder pro konkrétní Service třídy pomocí **Strategy Pattern** a **Registry Pattern**.

## Architektura

### 1. **DataBuilderInterface**
Definuje kontrakt pro všechny DataBuilder implementace.

### 2. **DataBuilderRegistry**
Centralizovaný registr pro správu DataBuilderů pro specifické Service třídy.

### 3. **BaseModelService**
Automaticky používá registrovaný DataBuilder nebo fallback na `buildDataCustom()`.

## Základní použití

### 1. Vytvoření vlastního DataBuilder

```php
<?php

use Pekral\Arch\Service\Contracts\DataBuilderInterface;
use Pekral\Arch\Service\DataBuilder;

class UserDataBuilder implements DataBuilderInterface
{
    public function build(Collection|array $data): array
    {
        return DataBuilder::create()
            ->setData($data)
            ->trimStrings()
            ->emptyStringsToNull()
            ->normalizeBooleans()
            ->addNormalizer(function (array $data): array {
                // User-specific logic
                if (isset($data['email'])) {
                    $data['email'] = strtolower($data['email']);
                }
                
                if (isset($data['name'])) {
                    $data['name'] = ucwords($data['name']);
                }
                
                return $data;
            })
            ->build();
    }
}
```

### 2. Registrace DataBuilder pro Service

#### A) Registrace třídy

```php
use Pekral\Arch\Service\DataBuilderRegistry;

// V Service Provider nebo bootstrap kódu
DataBuilderRegistry::register(
    \App\Services\UserService::class,
    \App\DataBuilders\UserDataBuilder::class
);
```

#### B) Registrace instance

```php
use Pekral\Arch\Service\DataBuilderRegistry;

// Registrace instance (pro složitější konfigurace)
$userBuilder = new UserDataBuilder();
DataBuilderRegistry::registerInstance(
    \App\Services\UserService::class,
    $userBuilder
);
```

### 3. Použití v Service

```php
class UserService extends BaseModelService
{
    // ... implementace abstraktních metod ...

    // DataBuilder se použije automaticky!
    public function createFromRequest(Request $request): User
    {
        return $this->create($request->all());
    }
}
```

## Pokročilé použití

### 1. Service Provider pro registraci

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Pekral\Arch\Service\DataBuilderRegistry;

class DataBuilderServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registrace DataBuilderů
        DataBuilderRegistry::register(
            \App\Services\UserService::class,
            \App\DataBuilders\UserDataBuilder::class
        );
        
        DataBuilderRegistry::register(
            \App\Services\ProductService::class,
            \App\DataBuilders\ProductDataBuilder::class
        );
        
        DataBuilderRegistry::register(
            \App\Services\OrderService::class,
            \App\DataBuilders\OrderDataBuilder::class
        );
    }
}
```

### 2. Fallback na buildDataCustom()

Pokud není DataBuilder registrovaný, Service použije `buildDataCustom()`:

```php
class UserService extends BaseModelService
{
    // ... implementace abstraktních metod ...

    protected function buildDataCustom(Collection|array $data): array
    {
        // Fallback implementace
        return DataBuilderFactory::forRequest($data)->build();
    }
}
```

### 3. Dočasná registrace

```php
class UserService extends BaseModelService
{
    public function createWithSpecialProcessing(array $data): User
    {
        // Dočasná registrace speciálního DataBuilder
        $specialBuilder = new SpecialUserDataBuilder();
        
        DataBuilderRegistry::registerInstance(static::class, $specialBuilder);
        
        try {
            $result = $this->create($data);
        } finally {
            // Vyčištění
            DataBuilderRegistry::unregister(static::class);
        }
        
        return $result;
    }
}
```

### 4. Dynamická registrace

```php
// Registrace na základě konfigurace
$dataBuilders = config('app.data_builders', []);

foreach ($dataBuilders as $service => $builder) {
    DataBuilderRegistry::register($service, $builder);
}
```

## Registry API

### Základní metody

```php
// Registrace třídy
DataBuilderRegistry::register(string $serviceClass, string $builderClass): void

// Registrace instance
DataBuilderRegistry::registerInstance(string $serviceClass, DataBuilderInterface $builder): void

// Získání DataBuilder
DataBuilderRegistry::getBuilder(string $serviceClass): ?DataBuilderInterface

// Kontrola existence
DataBuilderRegistry::hasBuilder(string $serviceClass): bool

// Zrušení registrace
DataBuilderRegistry::unregister(string $serviceClass): void

// Vyčištění všech registrací
DataBuilderRegistry::clear(): void

// Seznam registrovaných služeb
DataBuilderRegistry::getRegisteredServices(): array
```

### Priorita registrací

1. **Instance** má prioritu před **třídou**
2. Pokud není nic registrované, použije se `buildDataCustom()`

## Příklady DataBuilderů

### 1. UserDataBuilder

```php
class UserDataBuilder implements DataBuilderInterface
{
    public function build(Collection|array $data): array
    {
        return DataBuilder::create()
            ->setData($data)
            ->trimStrings()
            ->emptyStringsToNull()
            ->normalizeBooleans()
            ->addNormalizer(function (array $data): array {
                // Email normalizace
                if (isset($data['email'])) {
                    $data['email'] = strtolower(trim($data['email']));
                }
                
                // Jméno normalizace
                if (isset($data['name'])) {
                    $data['name'] = ucwords(strtolower(trim($data['name'])));
                }
                
                // Generování slug
                if (isset($data['name']) && !isset($data['slug'])) {
                    $data['slug'] = $this->generateSlug($data['name']);
                }
                
                // Výchozí hodnoty
                if (!isset($data['is_active'])) {
                    $data['is_active'] = true;
                }
                
                return $data;
            })
            ->build();
    }
}
```

### 2. ProductDataBuilder

```php
class ProductDataBuilder implements DataBuilderInterface
{
    public function build(Collection|array $data): array
    {
        return DataBuilder::create()
            ->setData($data)
            ->trimStrings()
            ->normalizeBooleans()
            ->addNormalizer(function (array $data): array {
                // Cena normalizace
                if (isset($data['price'])) {
                    $data['price'] = $this->normalizePrice($data['price']);
                }
                
                // SKU normalizace
                if (isset($data['sku'])) {
                    $data['sku'] = strtoupper(trim($data['sku']));
                }
                
                // Generování slug
                if (isset($data['name'])) {
                    $data['slug'] = $this->generateSlug($data['name']);
                }
                
                return $data;
            })
            ->build();
    }
}
```

## Výhody obecnějšího řešení

1. **Separation of Concerns** - DataBuilder logika je oddělená od Service
2. **Reusability** - Stejný DataBuilder pro více Service tříd
3. **Testability** - Snadné testování DataBuilderů samostatně
4. **Flexibility** - Možnost dynamické registrace a změny
5. **Maintainability** - Centralizovaná správa DataBuilderů
6. **Performance** - Instance se cachují v registru

## Best Practices

1. **Jeden DataBuilder na Service** - Pro jednoduchost a přehlednost
2. **Stateless DataBuilders** - Pro lepší performance a bezpečnost
3. **Registrace v Service Provider** - Pro centralizovanou správu
4. **Dokumentace** - Každý DataBuilder by měl být zdokumentován
5. **Testování** - Testujte DataBuildery samostatně
6. **Naming Convention** - `{Service}DataBuilder` pro konzistenci

## Konfigurace

### config/app.php

```php
'data_builders' => [
    \App\Services\UserService::class => \App\DataBuilders\UserDataBuilder::class,
    \App\Services\ProductService::class => \App\DataBuilders\ProductDataBuilder::class,
    \App\Services\OrderService::class => \App\DataBuilders\OrderDataBuilder::class,
],
```

### Service Provider

```php
public function boot(): void
{
    $dataBuilders = config('app.data_builders', []);
    
    foreach ($dataBuilders as $service => $builder) {
        DataBuilderRegistry::register($service, $builder);
    }
}
```

Toto řešení poskytuje maximální flexibilitu při zachování jednoduchosti použití!
