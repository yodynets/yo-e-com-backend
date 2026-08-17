# Package standards

This document records which coding standards the package adopts, *why*, and —
critically — **how each one is enforced** so compliance is a gate, not a habit.
It maps the project-wide rules in `standard.md` (repo root) onto this Laravel
package and explicitly lists what does **not** apply.

## Enforced standards

| Standard | What it means here | Enforced by | Gate |
|---|---|---|---|
| **PER-CS 2.0 / PSR-12** | Consistent code formatting | Pint (`laravel` preset) | `composer format:check` (`pint --test`) |
| **Strict typing** | `declare(strict_types=1)` in every file | Pint rule `declare_strict_types` (in `pint.json`) | `composer format` |
| **PSR-4 (autoload)** | Namespace ↔ path must match | Composer autoload + PHPStan class resolution | `composer dump-autoload --strict-psr` |
| **Static analysis at max** | PHPStan level `max` over `src/` + `tests/` | PHPStan + Larastan | `composer analyse` |
| **Strict comparisons** | No loose `==`/`!=`, iterables typed | `phpstan/phpstan-strict-rules` | `composer analyse` |
| **Strict enums** | Statuses are backed enums, no magic numbers | Convention (self-documenting) | review |
| **Onion / DDD layering** | `Domain` ≤ `Contracts`/`Exceptions`; `Application` ≤ `Domain`/`Contracts`/`Exceptions`; no `Illuminate` outside `Infrastructure` | PHPArkitect | `composer arch` |
| **readonly Value Objects** | Immutable VOs are `final readonly` | PHPStan (level max) | `composer analyse` |
| **No debug leftovers** | No `dd/dump/var_dump/print_r/ray/exit` in shipped code | PHPStan (planned via `spaze/phpstan-disallowed-calls`) | deferred — see below |

## Where the gates run

```bash
composer test            # PHPUnit
composer analyse         # PHPStan level max + Larastan + strict-rules + baseline
composer arch            # PHPArkitect Onion rules
composer format          # Pint (fix)
composer format:check    # Pint (dry-run)
```

`composer analyse` uses a **baseline** (`phpstan-baseline.neon`): pre-existing
noise in the Eloquent infrastructure layer and the intentional
`StatusIsolationTest` TypeError probes are recorded there, so the gate stays
green while still failing on **new** violations.

## Onion rules (PHPArkitect)

`phparkitect.php` enforces dependency direction across `src/`:

```text
Domain      must NOT depend on:  Application, Infrastructure, Illuminate
Application must NOT depend on:  Infrastructure, Illuminate
Infrastructure                 →  may depend on anything
```

This is the same check the original audit performed with a grep, but it now
runs as a permanent gate (`composer arch`).

## Laravel package conventions

Following the Laravel 13 package guidelines:

- **Service provider** with `register()` (`mergeConfigFrom`, container binds)
  and `boot()` (publishing).
- **`publishesMigrations()`** is used for migrations instead of the legacy
  `publishes()` form.
- **Config** is published under the `commerce-lifecycle-config` tag; migrations
  under `commerce-lifecycle-migrations`.
- **No facade** is provided (packages generally don't need one; consumers use
  the container / contracts).
- **Auto-discovery** is declared via `composer.json` → `extra.laravel.providers`.

## What from `standard.md` does NOT apply to this package

`standard.md` is written for the project's HTTP/DI stack (the host application).
These items are **out of scope** for this domain package and are intentionally
not adopted here:

- **PSR-7 / 11 / 15 / 17 / 18** — HTTP messages, server handlers, containers,
  factories, HTTP clients. This package is a framework-light domain kernel and
  does not touch HTTP; Laravel's container is used at the edge (Infrastructure).
- **RFC 9457 / `HttpStatusCode`** — HTTP error/problem-detail formatting.
  Package errors use the internal `Exceptions\*` hierarchy
  (`CommerceLifecycleException` and subclasses).
- **SAPI / RoadRunner / FastCGI** — runtime emission concerns of the host app.
- **PSR-3 / PSR-6 / PSR-13 / PSR-14 / PSR-16 / PSR-20** — logging, caching,
  links, event dispatcher, clock. The package ships its own minimal ports
  (`DomainEventDispatcher`) instead of adopting these.

## Deferred: debug-call enforcement (spaze)

`spaze/phpstan-disallowed-calls` (referenced by `standard.md`) is **not** wired
in yet: its current release is incompatible with the pinned PHPStan 2.2.x at
the DI level and breaks `composer analyse`. Until a compatible version lands,
the «no debug leftovers» rule is kept as a documented convention and I review
for `dd/dump/var_dump/print_r/ray/exit` before release. When compatible, add it
to `phpstan.neon` under `includes` with `disallowedFunctionCalls`.
