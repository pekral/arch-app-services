# PHPStan Architecture Rules

This project includes custom PHPStan rules to enforce architectural patterns and best practices.

## Overview

The custom rules enforce strict architectural separation to maintain clean, testable, and maintainable code.

### Architecture Layers & Responsibilities

```
┌─────────────────────────────────────────────────────────┐
│                       ACTION LAYER                       │
│  • Business logic orchestration                         │
│  • Simple retrieval without conditions (User::get())   │
│  • Delegates to Service layer                           │
└─────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────┐
│                      SERVICE LAYER                       │
│  • Coordinates Repository and ModelManager              │
│  • Transaction handling                                  │
│  • Complex business operations                           │
└─────────────────────────────────────────────────────────┘
            ↓                                    ↓
┌──────────────────────────┐      ┌──────────────────────────┐
│    REPOSITORY LAYER      │      │   MODELMANAGER LAYER     │
│  • Data RETRIEVAL        │      │  • Data PERSISTENCE      │
│  • SELECT with WHERE     │      │  • INSERT operations     │
│  • Query conditions      │      │  • UPDATE operations     │
│  • Eloquent scopes       │      │  • DELETE operations     │
│  • Aggregates with WHERE │      │  • Bulk operations       │
└──────────────────────────┘      └──────────────────────────┘
            ↓                                    ↓
            └──────────────┬─────────────────────┘
                          ↓
                   ┌──────────────┐
                   │   DATABASE   │
                   └──────────────┘
```

**Key Principles:**
- **Repository** → Data retrieval with conditions (SELECT queries with WHERE)
- **ModelManager** → Data persistence (INSERT, UPDATE, DELETE operations)
- **Actions** → Business logic coordination (NO direct database queries with conditions)

This ensures:
- Clear separation of concerns
- Improved testability
- Consistent data access patterns
- Easy to maintain and refactor

## Rules

### 1. NoEloquentStorageMethodsInActionsRule

**Purpose:** Prevents direct Eloquent storage method calls in Action classes.

**Rationale:** Actions should delegate data persistence to ModelManager classes. All INSERT, UPDATE, and DELETE operations must be encapsulated in ModelManager classes to maintain separation of concerns and ensure consistent data persistence patterns.

**Violations detected:**
- Calling `save()`, `create()`, `update()`, `delete()`, `forceDelete()` on Eloquent models in Action classes
- Calling `insert()`, `insertOrIgnore()`, `upsert()` on Eloquent models in Action classes
- Calling `updateOrCreate()`, `updateOrInsert()`, `firstOrCreate()`, `firstOrNew()` on Eloquent models in Action classes

**Example violation:**

```php
final readonly class CreateUser implements ArchAction
{
    public function __invoke(array $data): User
    {
        // ❌ Violation: Direct save() call in Action
        $user = new User($data);
        $user->save();
        
        return $user;
    }
}
```

**Correct approach:**

```php
final readonly class CreateUser implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function __invoke(array $data): User
    {
        // ✅ Correct: Data persistence through ModelManager
        // Service delegates to ModelManager internally
        return $this->userModelService->create($data);
    }
}
```

**Architecture flow:**
```
Action → Service → ModelManager → Database (INSERT/UPDATE/DELETE)
```

### 2. NoDirectDatabaseQueriesInActionsRule

**Purpose:** Prevents SQL queries with conditions (WHERE clauses or Eloquent scopes) in Action classes.

**Rationale:** Actions must delegate data operations to appropriate layers:
- **Repository classes** - for data retrieval with conditions (SELECT queries with WHERE)
- **ModelManager classes** - for data persistence (INSERT, UPDATE, DELETE operations)

Simple retrieval methods (`get()`, `all()`, `count()`, etc.) are allowed WITHOUT conditions. Any query with WHERE clauses or scopes must be encapsulated in Repository classes.

**Rule logic:**
1. **Allowed without conditions:** `get()`, `all()`, `first()`, `find()`, `count()`, `sum()`, `avg()`, `min()`, `max()`, `exists()`, `pluck()`
2. **Always forbidden:** `where()`, `whereIn()`, `orderBy()`, `limit()`, `join()`, `select()`, `with()`, `has()`, and all other query builder methods
3. **Eloquent scopes forbidden:** Any custom scope (e.g., `->active()`, `->published()`) cannot be used in Actions

**Violations detected:**

**Type 1: Direct query builder methods**
```php
// ❌ where(), whereIn(), orderBy(), etc. are always forbidden
User::where('active', true)->get();
User::whereIn('status', ['active', 'pending'])->count();
User::orderBy('created_at')->get();
```

**Type 2: Retrieval methods after conditions**
```php
// ❌ get(), count(), sum() etc. after WHERE or scopes
User::where('verified', true)->count();
User::active()->sum('points');  // active() is a scope
```

**Type 3: Eloquent scopes**
```php
// ❌ Custom scopes cannot be called in Actions
User::active()->get();
User::published()->count();
User::verified()->first();
```

**Allowed patterns:**

```php
final readonly class GetAllUsers implements ArchAction
{
    public function __invoke(): Collection
    {
        // ✅ Simple get() without conditions is allowed
        return User::get();
    }
}
```

```php
final readonly class CountAllUsers implements ArchAction
{
    public function __invoke(): int
    {
        // ✅ Simple count() without conditions is allowed
        return User::count();
    }
}
```

```php
final readonly class GetUserById implements ArchAction
{
    public function __invoke(int $id): ?User
    {
        // ✅ Simple find() is allowed
        return User::find($id);
    }
}
```

**Correct approach for queries with conditions:**

```php
final readonly class GetActiveUsers implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function __invoke(): Collection
    {
        // ✅ Correct: Data retrieval with conditions through Repository
        // Service delegates to Repository internally
        return $this->userModelService->findByParams(['active' => true]);
    }
}
```

```php
final readonly class CountVerifiedUsers implements ArchAction
{
    public function __construct(
        private UserModelService $userModelService
    ) {}
    
    public function __invoke(): int
    {
        // ✅ Correct: Aggregates with conditions through Repository
        // Service delegates to Repository internally
        return $this->userModelService->countByParams(['verified' => true]);
    }
}
```

**Architecture layers:**
```
Action → Service → Repository (for data retrieval with conditions)
Action → Service → ModelManager (for data persistence)
```

### 3. OnlyModelManagersCanPersistDataRule

**Purpose:** Ensures data persistence operations are only performed in ModelManager or ModelService classes.

**Rationale:** Centralizes data persistence logic in dedicated ModelManager or ModelService classes, making it easier to maintain, test, and audit database operations.

**Violations detected:**
- Any persistence method (`save()`, `create()`, `update()`, `delete()`, etc.) called outside of classes extending `BaseModelManager` or `BaseModelService`

**Example violation:**

```php
final readonly class SomeRandomClass
{
    public function updateUser(User $user, array $data): void
    {
        // ❌ Violation: Direct save() call outside allowed classes
        $user->fill($data);
        $user->save();
    }
}
```

**Correct approaches:**

```php
// ✅ Correct: Persistence logic in ModelManager
final class UserModelManager extends BaseModelManager
{
    protected function getModelClassName(): string
    {
        return User::class;
    }
    
    public function updateUser(User $user, array $data): bool
    {
        return $this->update($user, $data);
    }
}

// ✅ Also correct: Persistence logic in ModelService
final readonly class UserModelService extends BaseModelService
{
    public function deleteModel(Model $model): bool
    {
        $result = $model->delete();
        return $result !== null && $result;
    }
}
```

### 4. NoLaravelHelpersForActionsRule

**Purpose:** Prevents using Laravel helper functions (`app()`, `resolve()`, `make()`) to resolve Action classes. Actions must be injected via constructor dependency injection.

**Rationale:** Enforces proper dependency injection pattern for Action classes. Using service locator pattern (helper functions) makes code harder to test, less explicit, and violates dependency inversion principle. Constructor injection makes dependencies explicit and enables better testability.

**Violations detected:**
- Using `app()`, `resolve()`, or `make()` helper functions to resolve Action classes in Action classes
- Works with both class constants (`GetUser::class`) and variables containing Action class names

**Example violations:**

```php
final readonly class ProcessUser implements ArchAction
{
    public function __invoke(User $user): void
    {
        // ❌ Violation: Using app() helper with class constant
        $action = app(GetUser::class);
        
        // ❌ Violation: Using resolve() helper with class constant
        $action2 = resolve(GetUser::class);
        
        // ❌ Violation: Using make() helper with class constant
        $action3 = make(GetUser::class);
        
        // ❌ Violation: Using helper with variable containing Action class
        $actionClass = GetUser::class;
        $action4 = app($actionClass);
    }
}
```

**Correct approach:**

```php
final readonly class ProcessUser implements ArchAction
{
    public function __construct(
        private GetUser $getUserAction
    ) {}
    
    public function __invoke(User $user): void
    {
        // ✅ Correct: Action injected via constructor
        $user = ($this->getUserAction)(['id' => $user->id]);
    }
}
```

**Note:** This rule only applies within Action classes (classes implementing `ArchAction` interface). Using Laravel helpers for non-Action classes is not restricted by this rule.

### 5. ServiceNamingConventionRule

**Purpose:** Ensures that all Service classes extending `BaseModelService` follow a consistent naming convention by ending with `ModelService`.

**Rationale:** Enforces consistent naming conventions across the codebase, making it easier to identify Service classes and maintain code organization. This convention helps developers quickly recognize Service classes and their purpose.

**Violations detected:**
- Service classes extending `BaseModelService` that do not end with `ModelService` suffix

**Example violation:**

```php
// ❌ Violation: Service class does not end with ModelService
final readonly class UserService extends BaseModelService
{
    // ...
}
```

**Correct approach:**

```php
// ✅ Correct: Service class ends with ModelService
final readonly class UserModelService extends BaseModelService
{
    // ...
}
```

**Note:** This rule only applies to classes that extend `BaseModelService`. The base class itself (`BaseModelService`) is excluded from this check.

### 6. ActionInvokeMethodRule

**Purpose:** Guarantees that every Action class is invokable: it exposes exactly one public instance method, `__invoke()`, and no other public methods (the constructor is ignored).

**Rationale:** Actions should provide a single callable entry point. Extra public methods make orchestration unclear and encourage bypassing the intended flow.

The `final readonly` class modifiers are checked by [ActionClassModifiersRule](#13-actionclassmodifiersrule).

**Violations detected:**
- Missing the public `__invoke()` method
- Having any other public method (e.g., `execute()`, `handle()`, `process()`, helper methods)

**Example violation:**

```php
final readonly class ProcessUser implements ArchAction
{
    public function __invoke(User $user): void
    {
        // ...
    }

    public function notify(User $user): void
    {
        // ❌ Extra public method
    }
}
```

**Correct approach:**

```php
final readonly class ProcessUser implements ArchAction
{
    public function __invoke(User $user): void
    {
        // ✅ Single public entrypoint (invokable action)
    }
}
```

### 7. ValidationRulesMethodNamingRule

**Purpose:** Ensures that classes implementing `ValidationRules` interface follow strict conventions for method naming and structure.

**Rationale:** ValidationRules classes should be stateless utility classes with only static methods. All methods must end with `Rules` suffix for consistency and clarity. No constructor is allowed as these classes should never be instantiated.

**Violations detected:**
- Having a constructor in ValidationRules class
- Having non-static methods
- Methods not ending with `Rules` suffix

**Example violations:**

```php
final class UserValidationRules implements ValidationRules
{
    // ❌ Violation: Constructor not allowed
    public function __construct() {}
    
    // ❌ Violation: Method must be static
    public function email(): array {}
    
    // ❌ Violation: Method must end with "Rules" suffix
    public static function getEmail(): array {}
}
```

**Correct approach:**

```php
final class UserValidationRules implements ValidationRules
{
    public static function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'name' => ['required', 'max:255'],
        ];
    }
    
    public static function emailRules(): array
    {
        return ['required', 'email'];
    }
    
    public static function nameRules(): array
    {
        return ['required', 'max:255'];
    }
}
```

### 8. ValidationRulesNoInstantiationRule

**Purpose:** Prevents instantiation of classes implementing `ValidationRules` interface via `new` keyword.

**Rationale:** ValidationRules classes should be used statically only. Creating instances would be meaningless as all methods are static.

**Violations detected:**
- Creating instance of ValidationRules class using `new` keyword

**Example violation:**

```php
// ❌ Violation: ValidationRules class should not be instantiated
$rules = new UserValidationRules();
```

**Correct approach:**

```php
// ✅ Correct: Use static methods directly
$rules = UserValidationRules::rules();
$emailRules = UserValidationRules::emailRules();
```

### 9. NoHttpResponseInActionsRule

**Purpose:** Guarantees that no method of an Action returns an HTTP response.

**Rationale:** An Action returns data (a DTO, a model, a scalar). The controller owns the HTTP layer and builds the response from that data. An Action that returns a response is coupled to HTTP and cannot be reused from a job, a command, or another Action.

**Layers:** an Action is a class implementing `ArchAction`. A project whose Actions do not all implement `ArchAction` yet lists the namespaces that hold them with the optional `actionNamespaces` argument. A class in one of those namespaces counts as an Action even without `ArchAction`. Without the argument the rule inspects `ArchAction` implementations only. The argument works the same way as in `NoHttpExceptionsInActionsRule`.

**Inspected types:** the rule reads the declared (or PHPDoc) return type of every method regardless of visibility, and the type of every expression the method returns. A broad declaration such as `mixed`, `object`, or `\Stringable` therefore cannot hide a returned response. Nullable and union members are included. A method without a body (abstract or interface) is not inspected.

**Reported types:**
- `Symfony\Component\HttpFoundation\Response` and every subclass (`Illuminate\Http\JsonResponse`, `RedirectResponse`, `StreamedResponse`, `BinaryFileResponse`, ...)
- `Illuminate\Contracts\Support\Responsable` (for example `JsonResource`)
- `Illuminate\Contracts\View\View`
- `Illuminate\Contracts\Routing\ResponseFactory`
- `Inertia\Response` (matched by class name; the package does not depend on Inertia)

**Not reported:** a `spatie/laravel-data` DTO. It implements `Responsable`, but a DTO is the sanctioned Action result (see `ActionReturnDtoRule`). A method declared `mixed` that returns a scalar or a DTO is not reported either.

**Error identifier:** `arch.noHttpResponseInAction`

**Example violation:**

```php
final readonly class ShowReport implements ArchAction
{
    public function __invoke(int $id): JsonResponse
    {
        return new JsonResponse($this->reports->find($id)); // ❌ HTTP response in an Action
    }
}
```

**Correct approach:**

```php
final readonly class ShowReport implements ArchAction
{
    public function __invoke(int $id): ReportData
    {
        return ReportData::from($this->reports->find($id)); // ✅ data; the controller builds the response
    }
}
```

**Configuration of `actionNamespaces` (optional):**

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpResponseInActionsRule
        arguments:
            actionNamespaces:
                - App\Actions\
        tags:
            - phpstan.rules.rule
```

**Legitimate exception:** use PHPStan's standard `ignoreErrors` on the identifier, scoped to the Action file, in the consuming project:

```neon
parameters:
    ignoreErrors:
        -
            identifier: arch.noHttpResponseInAction
            path: app/Actions/Export/StreamExport.php
```

### 10. NoHttpExceptionsInActionsRule

**Purpose:** Guarantees that an Action never produces an HTTP response exception.

**Rationale:** An Action is not an HTTP entry point. It signals a failure with a domain exception, and the entry point (controller, exception handler) translates it into an HTTP response. An HTTP exception thrown in an Action couples it to HTTP and changes the response body of every caller.

**Layer:** an Action is a class implementing `ArchAction`. A project whose Actions do not all implement `ArchAction` yet lists the namespaces that hold them with the optional `actionNamespaces` argument.

**Reported:**
- `throw` of a Symfony `HttpExceptionInterface` or an Illuminate `HttpResponseException` (or a subclass)
- `throw_if()` / `throw_unless()` with such an exception as a class-string or as an instance
- every call of `abort()`, `abort_if()` and `abort_unless()`

**Error identifier:** `arch.noHttpExceptionInAction`

**Example violation:**

```php
final readonly class ShowReport implements ArchAction
{
    public function __invoke(int $id): ReportData
    {
        return ReportData::from($this->reports->find($id) ?? throw new NotFoundHttpException()); // ❌ HTTP exception in an Action
    }
}
```

**Correct approach:**

```php
final readonly class ShowReport implements ArchAction
{
    public function __invoke(int $id): ReportData
    {
        return ReportData::from($this->reports->findOrFail($id)); // ✅ ModelNotFoundException; the handler renders the 404
    }
}
```

**Configuration:** register the rule with the Action namespaces of the project (optional):

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInActionsRule
        arguments:
            actionNamespaces:
                - App\Actions\
        tags:
            - phpstan.rules.rule
```

**Legitimate exception:** use PHPStan's standard `ignoreErrors` on the identifier, scoped to the file. A path-scoped entry that no longer matches is reported, so the exception list shrinks as the code is fixed.

### 11. NoHttpExceptionsInDataValidatorsRule

**Purpose:** Guarantees that a Data Validator never produces an HTTP response exception.

**Rationale:** A Data Validator is not an HTTP entry point. It rejects input with a validation or domain exception, and the entry point translates it into an HTTP response.

**Layer:** a Data Validator is a class whose name ends with `DataValidator`. The optional `dataValidatorNamespaces` argument limits the rule to the listed namespace prefixes. Without the argument the rule inspects every class with the suffix.

**Reported:** the same constructs as `NoHttpExceptionsInActionsRule`.

**Error identifier:** `arch.noHttpExceptionInDataValidator`

**Example violation:**

```php
final class OrderDataValidator
{
    public function assertPayable(Order $order): void
    {
        abort_if($order->isPaid(), 409); // ❌ HTTP exception in a Data Validator
    }
}
```

**Correct approach:**

```php
final class OrderDataValidator
{
    public function assertPayable(Order $order): void
    {
        throw_if($order->isPaid(), OrderAlreadyPaidException::class); // ✅ domain exception; the handler renders the 409
    }
}
```

**Configuration:** register the rule, limited to the namespaces of the project (optional):

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInDataValidatorsRule
        arguments:
            dataValidatorNamespaces:
                - App\
        tags:
            - phpstan.rules.rule
```

**Legitimate exception:** use PHPStan's standard `ignoreErrors` on the identifier, scoped to the file.

### 12. ActionsMustImplementArchActionRule

**Purpose:** Guarantees that every class declared in an Actions namespace implements `ArchAction`.

**Rationale:** Every other Action rule recognises an Action by the `ArchAction` interface. A class in the Actions namespace that does not implement it silently escapes all of them.

**Configuration:** the rule reports nothing until the namespaces are configured:

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\ActionsMustImplementArchActionRule
        arguments:
            actionNamespaces:
                - App\Actions\
        tags:
            - phpstan.rules.rule
```

**Error identifier:** `arch.actionMustImplementInterface`

**Not reported:** an anonymous class, an interface, a trait, an enum, and a class outside the configured namespaces.

**Example violation:**

```php
namespace App\Actions;

final readonly class SendInvoice // ❌ does not implement ArchAction
{
    public function __invoke(int $id): void {}
}
```

### 13. ActionClassModifiersRule

**Purpose:** Guarantees that every concrete Action class is declared `final readonly`.

**Rationale:** A `final` Action cannot be modified through inheritance, and a `readonly` Action cannot replace its injected dependencies after construction. `ActionInvokeMethodRule` owns the public method contract; this rule owns only the class modifiers.

**Error identifiers:** `arch.actionNotFinal`, `arch.actionNotReadonly`

**Not reported:** an abstract class, an interface, a trait, an enum, and an anonymous class implementing `ArchAction`.

**Example violation:**

```php
final class CreateUserAction implements ArchAction // ❌ not readonly
{
    public function __invoke(): void {}
}

readonly class SendInvoiceAction implements ArchAction // ❌ not final
{
    public function __invoke(): void {}
}
```

**Correct approach:**

```php
final readonly class CreateUserAction implements ArchAction // ✅
{
    public function __construct(private UserModelService $users) {}

    public function __invoke(CreateUserDTO $data): User
    {
        // ...
    }
}
```

Actions generated by `php artisan make:arch-action` are already `final readonly`.

### 14. ImmutableDependenciesRule

**Purpose:** Guarantees that architecture-layer classes receive their dependencies through the constructor and never replace them afterwards.

**Applies to:** Actions (`ArchAction`), Services (`BaseModelService`), Repositories (`Repository`) and Model Managers (`ModelManager`). Layers are recognised by type, never by class-name suffix. DTOs, Eloquent models, value objects and any other class outside these layers are not checked.

**Rationale:** Explicit, immutable constructor injection keeps the object graph predictable. Setter or property injection lets a dependency be swapped after construction, which hides the real collaborators and breaks long-running workers.

**Violations detected:**
- A dependency stored in a property that is not `readonly` (a `readonly` class makes every property readonly) — identifier `arch.mutableDependency`
- A dependency assigned anywhere except the constructor: setter injection and reassignment — identifier `arch.dependencyAssignedOutsideConstructor`

A dependency is an instance property typed as an object. Properties typed as a DTO (`Spatie\LaravelData\Data`) or an Eloquent model hold data, not collaborators, and are not dependencies. Objects created inside a method are not dependencies either.

**Example violation:**

```php
final class CreateOrderAction implements ArchAction
{
    private PaymentService $paymentService; // ❌ not readonly

    public function setPaymentService(PaymentService $paymentService): void
    {
        $this->paymentService = $paymentService; // ❌ setter injection
    }
}
```

**Correct approach:**

```php
final readonly class CreateOrderAction implements ArchAction
{
    public function __construct(private PaymentService $paymentService) {} // ✅
}

final class OrderModelManager extends BaseModelManager
{
    public function __construct(private readonly AuditLog $auditLog) {} // ✅ readonly property in a non-readonly class
}
```

## Configuration

The rules are configured in `phpstan.neon`:

```neon
services:
    -
        class: Pekral\Arch\PHPStan\Rules\NoEloquentStorageMethodsInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoDirectDatabaseQueriesInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\OnlyModelManagersCanPersistDataRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoLaravelHelpersForActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ServiceNamingConventionRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ActionInvokeMethodRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ActionClassModifiersRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ImmutableDependenciesRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ValidationRulesMethodNamingRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\ValidationRulesNoInstantiationRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpResponseInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInActionsRule
        tags:
            - phpstan.rules.rule
    -
        class: Pekral\Arch\PHPStan\Rules\NoHttpExceptionsInDataValidatorsRule
        tags:
            - phpstan.rules.rule
```

## Exceptions

Some violations are intentionally ignored:

1. **Examples directory** (`examples/*`): Demo code may show both correct and incorrect patterns for educational purposes
2. **BaseModelManager** and **BaseModelService**: These base classes need to call Eloquent methods to implement the patterns

## Running the Rules

```bash
# Run full PHPStan analysis with custom rules
composer analyse

# Or directly with vendor binary
vendor/bin/phpstan analyse --memory-limit=2G
```

## Benefits

These rules provide:

1. **Architectural consistency**: Enforces separation of concerns across the codebase
2. **Maintainability**: Changes to data access logic are isolated to specific layers
3. **Testability**: Actions become easier to test by mocking Services/Repositories instead of database
4. **Code review**: Automatically catches architectural violations during development
5. **Documentation**: Rules serve as living documentation of architectural decisions

## See Also

- [Repository Caching](repository-caching.md)
- [Model Manager](model-manager.md)
- [Data Builder](data-builder.md)

