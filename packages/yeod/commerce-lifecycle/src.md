---
paths:
  - 'packages/yeod/commerce-lifecycle/**'
---

# Commerce Lifecycle — Package Context & Rules

## How to use this file

This is the single source of truth for the `yeod/commerce-lifecycle` package.
At the start of any session that touches `packages/yeod/commerce-lifecycle/**`,
read this file **before** planning or editing. It contains settled facts (no
need to re-discover them), standing rules, and the current state of the
FIX_VERIFICATION checklist. The audit trail lives in
`packages/yeod/AUDIT.md`; the checklist lives in
`packages/yeod/commerce-lifecycle/FIX_VERIFICATION.md`.

## Environment

| Fact | Value |
| --- | --- |
| PHP | 8.5.8 (CLI, NTS, VC++ 2022, x64) |
| pdo_sqlite | enabled |
| Composer | 2.10.2 |
| OS | Windows / PowerShell |
| Host app | Laravel 13 (monorepo at `K:/activity/php/pro/fila`) |
| Git | branch `main`, clean (all committed) |
| Package | developed as path-repository dependency of the host app |

## Package structure

```
src/
├── Application/                 # use-case services (framework-free)
│   ├── Archive/ArchiveService.php
│   ├── Fulfillment/TransitionFulfillment.php, FulfillmentSnapshot.php
│   ├── Authorizer.php (port), AllowAllAuthorizer.php, DenyAllAuthorizer.php
├── Domain/                      # domain layer, framework-free
│   ├── Archive/ArchiveRepository.php (port)
│   ├── Catalog/ProductAvailabilityStatus.php
│   ├── Events/DomainEvent.php, DomainEventDispatcher.php
│   ├── Fulfillment/Fulfillment.php, FulfillmentLine.php (final readonly),
│   │   FulfillmentRepository.php, FulfillmentStatus.php, FulfillmentStatusChanged.php
│   ├── Order/OrderStatus.php, Payment/PaymentStatus.php,
│   │   ReturnFlow/ReturnStatus.php, Shipment/ShipmentStatus.php
│   └── Shared/TransitionableStatus.php
├── Exceptions/                  # single hierarchy, base CommerceLifecycleException
├── Infrastructure/              # Laravel adapters
│   ├── Events/LaravelDomainEventDispatcher.php
│   ├── Laravel/CommerceLifecycleServiceProvider.php
│   └── Persistence/Eloquent/ (repositories + models + Casts/FulfillmentStatusCast.php)
tests/
├── Doubles/ (FakeArchiveRepository, FakeEventDispatcher, FakeFulfillmentRepository)
└── Unit/ (8 classes, 108 tests, 287 assertions)
config/commerce-lifecycle.php
database/migrations/2026_01_01_000000_create_commerce_lifecycle_tables.php
docs/ (api, architecture, database, positioning, standards, statuses)
```

## Architecture rules

### Contracts live in Domain\Events; no src/Contracts

M7 moved `DomainEvent`/`DomainEventDispatcher` from `src/Contracts` into
`src/Domain/Events`. Do not reintroduce a top-level `src/Contracts` directory;
domain ports/contracts go under `Domain/`. The two leftover `src/Contracts/*.php`
files (orphaned after the move) **must be deleted** — this is still pending.

### TransitionFulfillment auth + status cast semantics

`TransitionFulfillment` is bound in `CommerceLifecycleServiceProvider` with the
configured `Authorizer`; the default `DenyAllAuthorizer` is fail-closed, so
transitions are denied until the host binds an authorizer.
`FulfillmentStatusCast::set()` accepts a `FulfillmentStatus` OR a valid status
string, because `EloquentFulfillmentRepository` saves status as the enum's
string value — keep that behavior if the cast changes.

## Migration rules

### DB CHECK added per-driver, SQLite skips it

Laravel Blueprint has no cross-driver `check()` method. The M8 CHECK
(`fulfilled_quantity <= ordered_quantity`) is added via driver-aware raw
`ALTER TABLE ADD CONSTRAINT` for mysql/mariadb/pgsql/sqlsrv only; SQLite cannot
ALTER-add a CHECK and relies on the domain invariant in `FulfillmentLine`. Keep
this guard in the shared migration; don't emit CHECK via Blueprint.


## Settled facts

- **Layering (Onion/DDD):** Domain → Application → Infrastructure. Laravel is an
  adapter at the edge; the domain is framework-free. Verified by grep and by
  PHPArkitect: Domain imports nothing beyond `DateTimeImmutable` +
  `InvalidArgumentException`; Application has no `Illuminate`/`Infrastructure`;
  no `config()`/`app()`/`DB::`/`Carbon` outside `src/Infrastructure`.
- **Status axes:** six isolated enums — `OrderStatus`, `PaymentStatus`,
  `FulfillmentStatus`, `ShipmentStatus`, `ReturnStatus`,
  `ProductAvailabilityStatus`. Each implements `canTransitionTo(self $target)`
  (self-typed, so mixing contexts is a `TypeError`) and the shared
  `TransitionableStatus` contract with `isFinal()`.
- **Fulfillment aggregate:** status is derived from line quantities
  (scheduled → unfulfilled → partially_fulfilled → fulfilled). `fulfillLine()`
  validates before any mutation (H1+H2+H4); lines are immutable
  (`FulfillmentLine::withFulfilled()`).
- **Optimistic concurrency:** aggregate `version`; save only succeeds when the
  stored version matches; otherwise `StaleAggregateException`. In-memory version
  bumps only after commit (B4). `replaceLines()` uses `upsert()` +
  `whereNotIn()->delete()`.
- **Fulfillment lines PK:** composite `['fulfillment_id', 'id']` (B1); line ids
  are unique within an aggregate only. `FulfillmentLineModel` has
  `$incrementing=false`, `$timestamps=false`, `$keyType='string'`,
  `$primaryKey=null`.
- **Archive:** append-only, versioned (`snapshot_version`); `archive()` appends,
  `findSnapshot()` returns the deepest/latest non-restored snapshot
  (`whereNull('restored_at')` + `orderByDesc('snapshot_version')`), `restore()`
  marks the latest row. `ArchiveService` guards `archive`, `restore`,
  `findSnapshot`, `isArchived` through the `Authorizer` port; default
  `DenyAllAuthorizer` is fail-closed, `AllowAllAuthorizer` is dev-only.
  Snapshot size (`max_snapshot_size` KB) and reason length
  (`max_reason_length`) limits via constructor options.
- **Exceptions:** one hierarchy — `CommerceLifecycleException` base;
  `InvalidArgumentException` (extends base), `InvalidTransitionException`,
  `NotAuthorizedException`, `StaleAggregateException`.
- **Event delivery:** at-most-once. Repository persists in its own transaction;
  `TransitionFulfillment` dispatches emitted events via the
  `DomainEventDispatcher` port *after* commit. At-least-once requires a host
  outbox.
- **Migrations:** tables `commerce_fulfillments`, `commerce_fulfillment_lines`,
  `commerce_archives`; DB CHECK per-driver (see rule above); archives have a
  unique `(archivable_type, archivable_id, snapshot_version)`.
- **Config:** `authorizer` (default `DenyAllAuthorizer::class`),
  `max_snapshot_size` (512 KB), `max_reason_length` (1000 chars),
  `max_metadata_size` (65535 bytes).

## Quality gates (all green as of last run)

```bash
composer dump-autoload --optimize --strict-psr   # OK
composer test          # PHPUnit 12.5.33 — OK (108 tests, 287 assertions)
composer analyse       # PHPStan level max + larastan + strict-rules + baseline — 0 errors
composer arch          # PHPArkitect 1.3 — no violations
composer format        # Pint (laravel preset + declare_strict_types) — PASS
```

PHP 8.3+ and Laravel `^12.0|^13.0` (illuminate) are supported. Run package
tests from the host root with
`vendor/bin/phpunit --bootstrap vendor/autoload.php packages/yeod/commerce-lifecycle/tests/Unit`
(requires pdo_sqlite), or standalone after extraction with `composer test`.

## FIX_VERIFICATION state

- [x] **Stage 0–2 (D1–D5, B1–B5, H1–H9)** — done, gates passed.
- [x] **Stage 3 (M1–M9)** — gates passed 2026-08 run (test 116 · analyse 0 · arch 0 · format PASS); `src/Contracts/` (2 orphan files) deleted.
- [x] **Stage 4 (L + M10–M13)** — all items done:
  - [x] L1 (`@package fila`) removed in source
  - [x] L2 (self-namespace import in Fulfillment.php) removed
  - [x] L3 (justification comments in EloquentArchiveRepository) removed
  - [x] L4 — LICENSE: `Copyright (c) 2026 Yevhen Odynets`
  - [x] L5 — composer.json description honest
  - [x] L6 — phpstan: tests in paths, parallel, baseline
  - [x] M10 — CI `tests.yml`: composer order, 3 illuminate constrained, `fail-fast: false`, `prefer-lowest`, cache
  - [x] M11 — README: «Laravel 13» removed, host-path notes → CONTRIBUTING, Locad URL
  - [x] M12 — `phpunit.xml.dist` failOn* + `<source>`; test isolation (`tearDownAfterClass`); data-provider for isolation tests; Application tests (ArchiveService validation, NotAuthorizedException, unknown-id transition, fulfilled>ordered)
  - [x] M13 — `CHANGELOG.md`, `SECURITY.md`, `CONTRIBUTING.md`, `.editorconfig`, issue templates
- [x] **Stage 5 (standards)** — done (Pint, PHPStan max, PHPArkitect, baseline, composer scripts). `spaze/phpstan-disallowed-calls` deferred (incompat with phpstan 2.2.8 — see docs/standards.md).
- [ ] **Stage 6 (release)** — not started. Plan: `0.9.0` with «API may change before 1.0» (after Stage 4), then `1.0` only after M1/M2/M7/M9 BC-breaks are stable.

## Commit conventions

One commit = one audit item, Conventional style:
`fix(B1): composite PK for fulfillment lines`,
`tests(H1): no mutation on failed fulfillLine`,
`chore(M9): prepare composer for Packagist`.

