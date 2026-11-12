# Security Policy

## What this package is

`pekral/arch-app-services` is a Laravel library that runs inside your application. It provides Actions,
Repositories that read records, Model Managers that create, update and delete them, Model Services that combine
the two, repository caching, transaction helpers, and data validation and transformation. The SQL implementations
build on Eloquent; the optional DynamoDB implementations build on `baopham/dynamodb`.

It also ships Artisan generators that write new classes into your application from the stubs in `src/stubs/`,
and PHPStan rules in `phpstan/` that only run during static analysis.

That shape bounds what a vulnerability here could be. The realistic cases are code in `src/` that lets input
reach a query, a write, or a cache key in a way the caller did not intend, or a generator stub that produces
unsafe code in your application.

## Supported versions

Fixes go into the latest release. Older tags are not patched.

## Reporting a vulnerability

Report privately through GitHub's
[**Report a vulnerability**](https://github.com/pekral/arch-app-services/security/advisories/new) form, which
opens an advisory visible only to the maintainer.

Include the affected version and the steps to reproduce.

**Do not open a public issue for a vulnerability.** This is a single-maintainer project, so a public report
gives every consumer an exploit before there is a fix.

## Out of scope

- **A vulnerability in Laravel, Spatie Laravel Data, or `baopham/dynamodb`** belongs to that project. Report it
  upstream; this package only builds on them.
- **A PHPStan rule that misses or wrongly reports an architecture violation** is not a vulnerability. The rules
  run only during static analysis. Open a [bug report](https://github.com/pekral/arch-app-services/issues/new?template=bug_report.yml).
- **Development dependencies** (Pest, PHPStan, Pint, PHPCS, Rector, Testbench and the rest of `require-dev`)
  are not installed when an application requires this package. A `composer audit` runs in the
  [Checkers](https://github.com/pekral/arch-app-services/actions/workflows/checkers.yml) workflow on every
  pull request, every push to the default branch, and weekly regardless.
