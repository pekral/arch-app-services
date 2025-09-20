# DataBuilder - Custom Data Normalization

DataBuilder umožňuje programátorovi definovat vlastní logiku pro normalizaci dat před validací a databázovými operacemi.

## Základní použití

### 1. Override buildData() v Service třídě

```php
<?php

use Pekral\Arch\Service\BaseModelService;
use Pekral\Arch\Service\DataBuilder;

class UserService extends BaseModelService
{
    // ... implementace abstraktních metod ...

    /**
     * Override buildData() pro vlastní normalizaci dat.
     */
    protected function buildData(Collection|array $data): array
    {
        return $this->createDataBuilder($data)
            ->trimStrings()
            ->emptyStringsToNull()
            ->normalizeBooleans()
            ->normalizeDates()
            ->addNormalizer(function (array $data): array {
                // Vlastní logika pro User model
                
                // Normalizace emailu
                if (isset($data['email'])) {
                    $data['email'] = strtolower(trim($data['email']));
                }
                
                // Normalizace jména
                if (isset($data['name'])) {
                    $data['name'] = ucwords(strtolower(trim($data['name'])));
                }
                
                // Generování slug z jména
                if (isset($data['name']) && !isset($data['slug'])) {
                    $data['slug'] = $this->generateSlug($data['name']);
                }
                
                // Výchozí hodnoty
                if (!isset($data['is_active'])) {
                    $data['is_active'] = true;
                }
                
                // Odstranění citlivých polí
                unset($data['password_confirmation']);
                unset($data['_token']);
                
                return $data;
            })
            ->build();
    }
}
```

### 2. Automatické volání buildData()

DataBuilder se automaticky volá v metodách `create()` a `updateByParams()`:

```php
// buildData() se zavolá automaticky před validací
$user = $userService->create($requestData);

// buildData() se zavolá automaticky před validací
$userService->updateByParams($updateData, ['id' => $userId]);
```

### 3. Vlastní DataBuilder instance

Pro speciální případy můžete vytvořit vlastní DataBuilder:

```php
public function createFromApi(array $apiData): User
{
    $normalizedData = $this->createDataBuilder($apiData)
        ->trimStrings()
        ->normalizeBooleans()
        ->addNormalizer(function (array $data): array {
            // API-specific processing
            if (isset($data['external_id'])) {
                $data['api_id'] = $data['external_id'];
                unset($data['external_id']);
            }
            
            return $data;
        })
        ->build();

    return $this->create($normalizedData);
}
```

## Dostupné metody DataBuilder

### Základní normalizace

```php
$builder = DataBuilder::create()
    ->setData($data)
    ->trimStrings()                    // Otrimuje všechny stringy
    ->emptyStringsToNull()             // Převádí prázdné stringy na null
    ->normalizeBooleans()              // Normalizuje boolean hodnoty
    ->normalizeDates()                 // Normalizuje datumy
    ->build();
```

### Vlastní normalizace

```php
$builder = DataBuilder::create()
    ->setData($data)
    ->addNormalizer(function (array $data): array {
        // Vlastní logika
        if (isset($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }
        
        return $data;
    })
    ->build();
```

### Reset a opakované použití

```php
$builder = DataBuilder::create();

// První použití
$result1 = $builder->setData($data1)->trimStrings()->build();

// Reset
$builder->reset();

// Druhé použití
$result2 = $builder->setData($data2)->normalizeBooleans()->build();
```

## Factory metody

### DataBuilderFactory

```php
use Pekral\Arch\Service\DataBuilderFactory;

// Pro request data (základní normalizace)
$data = DataBuilderFactory::forRequest($requestData)->build();

// Pro form data (s normalizací dat)
$data = DataBuilderFactory::forForm($formData)->build();

// Pro API data (striktní normalizace)
$data = DataBuilderFactory::forApi($apiData)->build();

// Vlastní normalizace
$data = DataBuilderFactory::custom($data, [
    'trim',
    'empty_to_null',
    'booleans',
    'dates'
])->build();
```

## Příklady použití

### 1. Request processing

```php
public function createFromRequest(Request $request): User
{
    // buildData() se zavolá automaticky
    return $this->create($request->all());
}
```

### 2. Form processing s dodatečnou logikou

```php
public function createFromForm(array $formData): User
{
    $processedData = $this->createDataBuilder($formData)
        ->trimStrings()
        ->emptyStringsToNull()
        ->normalizeBooleans()
        ->normalizeDates()
        ->addNormalizer(function (array $data): array {
            // Form-specific processing
            if (isset($data['birth_date'])) {
                $data['age'] = $this->calculateAge($data['birth_date']);
            }
            
            return $data;
        })
        ->build();

    return $this->create($processedData);
}
```

### 3. Bulk processing

```php
public function processBulkUsers(Collection $bulkData): Collection
{
    $results = collect();

    foreach ($bulkData as $userData) {
        // Každý item se zpracuje pomocí buildData()
        $results->push($this->create($userData));
    }

    return $results;
}
```

## Výhody

1. **Centralizovaná logika** - Všechna normalizace dat na jednom místě
2. **Automatické volání** - DataBuilder se volá automaticky před validací
3. **Flexibilita** - Můžete definovat vlastní normalizační logiku
4. **Znovupoužitelnost** - Stejná logika pro create i update operace
5. **Testovatelnost** - Snadné testování normalizační logiky
6. **Konzistence** - Zajišťuje konzistentní zpracování dat

## Best Practices

1. **Override buildData()** pro základní normalizaci v Service třídě
2. **Používejte addNormalizer()** pro specifickou logiku
3. **Testujte normalizační logiku** samostatně
4. **Dokumentujte vlastní normalizace** v komentářích
5. **Používejte type hints** pro lepší IDE podporu
