# Contributing to Commerce Lifecycle

Thanks for taking the time to contribute. This document explains how the
package is built, tested, and how to get changes merged.

## Environment

- PHP 8.3+ (package is developed and tested on PHP 8.5).
- `pdo_sqlite` is required to run the test suite.
- Composer 2.x.

## Setup

```bash
composer install
```

## Quality gates

Every contribution must pass all four gates before it is considered done:

```bash
composer dump-autoload --optimize --strict-psr   # PSR-4 is valid
composer test          # PHPUnit with failOnWarning/failOnRisky/failOnDeprecation
composer analyse       # PHPStan level max + Larastan + strict-rules (+ baseline)
composer arch          # PHPArkitect Onion / DDD layer rules
```

`composer format` (Pint) fixes code style; use `composer format:check` for a
dry run before committing.

## Commit conventions

One commit = one focused change, in Conventional style:

```
fix(B1): composite PK for fulfillment lines
tests(H1): no mutation on failed fulfillLine
chore(M9): prepare composer for Packagist
```

## Layering rules

- `Domain/` is framework-free: no `Illuminate`, no facades, no `config()`,
  no `app()`, no `Carbon` outside `src/Infrastructure`.
- `Application/` must not import from `Infrastructure/`.
- Contracts (ports) live under `Domain\Events` — do not reintroduce a
  top-level `src/Contracts` directory.
- The default `DenyAllAuthorizer` is fail-closed; never widen it by default.
- Do not drop the per-driver DB CHECK guard from the shared migration
  (SQLite intentionally skips it).

See [docs/standards.md](docs/standards.md) and
[docs/architecture.md](docs/architecture.md) for details.

## Running tests as a path-repository dependency of a host app

The package is developed in a monorepo and is installed into the host app as a
path-repository dependency. From the **host project root**, reusing the host's
autoloader and PHPUnit:

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php packages/yeod/commerce-lifecycle/tests/Unit
```

> Requires `pdo_sqlite` — if it is not enabled in `php.ini`, prepend
> `php -d extension=pdo_sqlite` to the command.

When the package is extracted into its own repository, the standalone flow from
the [README](README.md) applies instead.