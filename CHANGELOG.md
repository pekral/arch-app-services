# Changelog

All notable changes to `arch-app-services` will be documented in this file.

## [Unreleased] - 2026-01-11 (PR #61)


- 📝 **Changed**: fixed #59 - new phpstan rule or model naming conventions
- ♻️ **Refactored**: DataBuilder::class and ignore DynamoDB instance in git
- 📝 **Changed**: tests: fix tests
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: CLAUDE.md file added
- 🔧 **Changed**: update cursor rules
- 🐛 **Fixed**: fix coerrect namespace for BuilderPipe::class
- 📝 **Changed**: chore - composer update dependencies
- 📝 **Changed**: WIP feat - apply dynamodb use cases

## [Unreleased] - 2026-01-10


- ♻️ **Refactored**: DataBuilder::class and ignore DynamoDB instance in git
- 📝 **Changed**: tests: fix tests
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: CLAUDE.md file added
- 🔧 **Changed**: update cursor rules
- 🐛 **Fixed**: fix coerrect namespace for BuilderPipe::class
- 📝 **Changed**: chore - composer update dependencies
- 📝 **Changed**: WIP feat - apply dynamodb use cases
- 📝 **Changed**: fix - docker restart
- ✨ **Added**: DynamoDb example with UserDynamoModel::class
- 📝 **Changed**: fix - dynamodb migrations

## [Unreleased] - 2025-11-23 (PR #58)


- 📝 **Changed**: fix - docker restart
- ✨ **Added**: DynamoDb example with UserDynamoModel::class
- 📝 **Changed**: fix - dynamodb migrations

## [Unreleased] - 2025-11-23 (PR #57)


- 🔧 **Changed**: prepare db dynamodb migrations
- 📝 **Changed**: refactor - small app cleanup
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: composer update dependencies

## [Unreleased] - 2025-11-22


- 📝 **Changed**: refactor - small app cleanup
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: composer update dependencies
- 🔧 **Changed**: init docker for dynamodb

## [Unreleased] - 2025-11-22 (PR #54)


- 🔧 **Changed**: init docker for dynamodb

## [Unreleased] - 2025-11-22 (PR #47)


- 📝 **Changed**: fix - phpstan errors

## [Unreleased] - 2025-11-21 (PR #46)


- 📝 **Changed**: refactoring - cleanup

## [Unreleased] - 2025-11-21 (PR #45)


- 📝 **Changed**: fix - example models

## [Unreleased] - 2025-11-21 (PR #44)


- 📝 **Changed**: refactor - interface for model managers
- 📝 **Changed**: refactoring - create repository interface

## [Unreleased] - 2025-11-21 (PR #43)


- 🐛 **Fixed**: phpstan ServiceNamingConventionRule fixed

## [Unreleased] - 2025-11-21 (PR #42)


- 📝 **Changed**: ActionExecuteMethodRule::class
- 🔧 **Changed**: composer update dependencies

## [Unreleased] - 2025-11-21


- 🔧 **Changed**: composer update dependencies
- 📝 **Changed**: resolve #31 feat: new phpstan rule

## [Unreleased] - 2025-11-13 (PR #38)


- 📝 **Changed**: resolve #31 feat: new phpstan rule

## [Unreleased] - 2025-11-13 (PR #37)


- 📝 **Changed**: resolve #32 feat: batch delete by params

## [Unreleased] - 2025-11-13 (PR #35)


- 🔧 **Changed**: new NoLaravelHelpersForActionsRule for PHPStan

## [Unreleased] - 2025-11-13 (PR #36)


- 📝 **Changed**: resolve #33 feat: ModelManager::class support direct remove model  # Please enter the commit message for your changes. Lines starting
- 🐛 **Fixed**: remove bad command
- 🔧 **Changed**: composer update dependencies
- 📚 **Documentation**: refresh docs

## [Unreleased] - 2025-11-13


- 🐛 **Fixed**: remove bad command
- 🔧 **Changed**: composer update dependencies
- 📚 **Documentation**: refresh docs
- 🐛 **Fixed**: more tests
- 🐛 **Fixed**: phpstan ignore treatPhpDocTypesAsCertain
- 🐛 **Fixed**: phpstan erros fix
- 🐛 **Fixed**: modify phpstan rules
- 🐛 **Fixed**: phpstan errors in test has gone away
- ✨ **Added**: beta of PHPStan rules for check arch
- 🐛 **Fixed**: test in PEST style now

## [Unreleased] - 2025-11-12


- 📚 **Documentation**: refresh docs
- 🐛 **Fixed**: more tests
- 🐛 **Fixed**: phpstan ignore treatPhpDocTypesAsCertain
- 🐛 **Fixed**: phpstan erros fix
- 🐛 **Fixed**: modify phpstan rules
- 🐛 **Fixed**: phpstan errors in test has gone away
- ✨ **Added**: beta of PHPStan rules for check arch
- 🐛 **Fixed**: test in PEST style now

## [Unreleased] - 2025-11-12 (PR #29)


- 🐛 **Fixed**: more tests
- 🐛 **Fixed**: phpstan ignore treatPhpDocTypesAsCertain
- 🐛 **Fixed**: phpstan erros fix
- 🐛 **Fixed**: modify phpstan rules
- 🐛 **Fixed**: phpstan errors in test has gone away
- ✨ **Added**: beta of PHPStan rules for check arch
- 🐛 **Fixed**: test in PEST style now

## [Unreleased] - 2025-11-12


- 🐛 **Fixed**: test in PEST style now
- 📝 **Changed**: resolve #18 feat: package check commands
- 🐛 **Fixed**: running pest coverage
- ✨ **Added**: Custom PHPStan rules for enforcing architectural patterns
  - NoEloquentStorageMethodsInActionsRule: Prevents direct Eloquent storage method calls in Actions
  - NoDirectDatabaseQueriesInActionsRule: Prevents direct database queries in Actions
  - OnlyModelManagersCanPersistDataRule: Ensures data persistence only in ModelManager or ModelService classes
- 📝 **Added**: PHPStan rules documentation in docs/phpstan-rules.md
- 🔧 **Changed**: OnlyModelManagersCanPersistDataRule now allows persistence methods in BaseModelService classes

## [Unreleased] - 2025-11-12 (PR #28)


- 📝 **Changed**: resolve #18 feat: package check commands
- 🐛 **Fixed**: running pest coverage

## [Unreleased] - 2025-11-12


- 🐛 **Fixed**: running pest coverage
- 📝 **Changed**: solve #25 feat: BaseModelService::getOrCreate method

## [Unreleased] - 2025-11-12 (PR #26)


- 📝 **Changed**: solve #25 feat: BaseModelService::getOrCreate method

## [Unreleased] - 2025-11-12 (PR #27)


- 📝 **Changed**: resolve #23 feat: cache repository support driver now

## [Unreleased] - 2025-11-12 (PR #20)


- ✨ **Added**: support mass update from iksaku/laravel-mass-update package

## [Unreleased] - 2025-11-11 (PR #19)


- ♻️ **Refactored**: use PEST instead of PHPUnit
- 🔧 **Changed**: composer update dependencies

## [Unreleased] - 2025-11-11


- 🔧 **Changed**: composer update dependencies
- 📝 **Changed**: resolve #11 feat: UpdateOrCreate method in service model created

## [Unreleased] - 2025-11-02 (PR #16)


- 📝 **Changed**: resolve #11 feat: UpdateOrCreate method in service model created

## [Unreleased] - 2025-11-02 (PR #14)


- 📝 **Changed**: solve #10 feat: update model via model manager now

## [Unreleased] - 2025-11-02 (PR #13)


- ✨ **Added**: create new model instace have public visilibility now

## [Unreleased] - 2025-11-02 (PR #12)


- 🔧 **Changed**: composer update dependecnies
- 🔧 **Changed**: composer update via github action now
- 🔧 **Changed**: update dependencies
- ✨ **Added**: model manager support insert or ignore action

## [Unreleased] - 2025-10-26


- 🔧 **Changed**: update dependencies
- ✨ **Added**: model manager support insert or ignore action
- 📝 **Changed**: resolved #5 feat: apply cache layer for repository

## [Unreleased] - 2025-10-19


- ✨ **Added**: model manager support insert or ignore action
- 📝 **Changed**: resolved #5 feat: apply cache layer for repository

## [Unreleased] - 2025-10-19 (PR #9)


- 📝 **Changed**: resolved #5 feat: apply cache layer for repository

## [Unreleased] - 2025-10-19 (PR #8)


- 🐛 **Fixed**: phpcs errors
- ✨ **Added**: action have logging support now
- 🐛 **Fixed**: change abstracts methods visibility
- 🔧 **Changed**: composer update dependencies
- ✨ **Added**: repositories support query as fluent interface
- 📚 **Documentation**: update docs
- ✨ **Added**: builder support specific pipelines
- ✨ **Added**: support simple validation
- 📝 **Changed**: chore - update dependencies
- 🔧 **Changed**: more todos
- ♻️ **Refactored**: cleanup old comments
- 🔧 **Changed**: refresh todo list
- 🔧 **Changed**: update readme.md
- 📝 **Changed**: tests: more tests
- 📝 **Changed**: tests: more tests
- 📝 **Changed**: tests: import users have tests now
- 🔧 **Changed**: cleanup dead codey
- 📝 **Changed**: tests: count by params for users have tests now
- 📝 **Changed**: tests: test for paginated users
- 📝 **Changed**: tests: get one uder by paramas test
- 📝 **Changed**: tests: tests for filter user by params
- ✅ **Tests**: Delte user via action now
- ✅ **Tests**: user can update only name via ation now
- ♻️ **Refactored**: verify user model via action now
- 📚 **Documentation**: refresh docs for DataBuilde::class
- ♻️ **Refactored**: renamed parameter for finally closure in DataBuildeR::class
- ✨ **Added**: DataBuilder::class support finally closure
- ♻️ **Refactored**: simplify action executes
- 🔧 **Changed**: composer update dependencies

## [Unreleased] - 2025-10-19


- 🐛 **Fixed**: change abstracts methods visibility
- 🔧 **Changed**: composer update dependencies
- ✨ **Added**: repositories support query as fluent interface
- 📚 **Documentation**: update docs
- ✨ **Added**: builder support specific pipelines
- ✨ **Added**: support simple validation
- 📝 **Changed**: chore - update dependencies
- 🔧 **Changed**: more todos
- ♻️ **Refactored**: cleanup old comments
- 🔧 **Changed**: refresh todo list
- 🔧 **Changed**: update readme.md
- 📝 **Changed**: tests: more tests
- 📝 **Changed**: tests: more tests
- 📝 **Changed**: tests: import users have tests now
- 🔧 **Changed**: cleanup dead codey
- 📝 **Changed**: tests: count by params for users have tests now
- 📝 **Changed**: tests: test for paginated users
- 📝 **Changed**: tests: get one uder by paramas test
- 📝 **Changed**: tests: tests for filter user by params
- ✅ **Tests**: Delte user via action now
- ✅ **Tests**: user can update only name via ation now
- ♻️ **Refactored**: verify user model via action now
- 📚 **Documentation**: refresh docs for DataBuilde::class
- ♻️ **Refactored**: renamed parameter for finally closure in DataBuildeR::class
- ✨ **Added**: DataBuilder::class support finally closure
- ♻️ **Refactored**: simplify action executes
- 🔧 **Changed**: composer update dependencies
- ✨ **Added**: Update user action
- ✨ **Added**: create new with with notification now
- 🔧 **Changed**: composer update dependencies
- 📝 **Changed**: feature(builder) - build data via builder now in execute action

## [Unreleased] - 2025-10-03


- 📚 **Documentation**: refresh docs for DataBuilde::class
- ♻️ **Refactored**: renamed parameter for finally closure in DataBuildeR::class
- ✨ **Added**: DataBuilder::class support finally closure
- ♻️ **Refactored**: simplify action executes
- 🔧 **Changed**: composer update dependencies
- ✨ **Added**: Update user action
- ✨ **Added**: create new with with notification now
- 🔧 **Changed**: composer update dependencies
- 📝 **Changed**: feature(builder) - build data via builder now in execute action

## [Unreleased] - 2025-10-01 (PR #3)


- ✨ **Added**: Update user action
- ✨ **Added**: create new with with notification now
- 🔧 **Changed**: composer update dependencies
- 📝 **Changed**: feature(builder) - build data via builder now in execute action

## [Unreleased] - 2025-09-24 (PR #2)


- 📝 **Changed**: feature(data builders) - init basic data builders
- 📝 **Changed**: tests(user) - more tests in examples folder
- 📝 **Changed**: chore(composer) - update dependencies
- 📝 **Changed**: feat(repository) - more options

## [Unreleased] - 2025-09-19 (PR #1)


- 📝 **Changed**: 

## [Unreleased] - 2025-09-19


- 📝 **Changed**: App - init
- 📝 **Changed**: Initial commit

## 1.0.0 - 2025-09-18

### Added
- Initial release
- Abstract Facade pattern implementation
- Repository pattern with MySQL support
- Model Manager with CRUD operations
- Laravel 12 compatibility
- Comprehensive type annotations
- Pest testing framework integration
- PHPStan level max analysis
- Service provider auto-discovery
- Configuration publishing
