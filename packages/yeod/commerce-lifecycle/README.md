# Commerce Lifecycle for Laravel 13

A framework-light DDD/Onion module for modeling the lifecycle of commerce records without collapsing order, payment, fulfillment, shipment, return, catalog availability, and archival into one overloaded status field.

## Design decision

This package follows the useful distinction described by Locad:

- **Order status** describes the commercial order and the next customer or operator action.
- **Payment status** describes money movement.
- **Fulfillment status** describes whether order lines are scheduled, unfulfilled, partially fulfilled, or fulfilled.
- **Shipment status** describes the physical package in transit.
- **Return status** describes the reverse-logistics flow.
- **Catalog availability** describes whether a product may appear in or be sold from the catalog.
- **Archive state** is orthogonal lifecycle metadata. It is not a fake business status and does not delete the record.

The package intentionally does not make Eloquent models the domain model. Laravel is an adapter at the edge; the domain can be unit-tested without the framework.

> **Positioning:** this is a *lifecycle kernel*, not an e-commerce application.
> It models statuses, transitions, fulfillment, and archival — it does not model
> your shop's `orders`, `products`, `suppliers`, or `order_items`. Those live in
> the host application and link to the package by plain identifiers. See
> [docs/positioning.md](docs/positioning.md) for the boundary and how to reuse it
> as a full merchant domain.

## Installation

```bash
composer require yeod/commerce-lifecycle
php artisan vendor:publish --tag=commerce-lifecycle-config
php artisan vendor:publish --tag=commerce-lifecycle-migrations
php artisan migrate
```

## Documentation

- [API reference](docs/api.md) — every public method with code examples.
- [Database schema](docs/database.md) — tables, indexes, and the rationale behind
  each design decision.
- [Statuses reference](docs/statuses.md) — meaning of every status value.
- [Architecture notes](docs/architecture.md) — layering and design decisions.
- [Positioning](docs/positioning.md) — why it is a separate package and how it
  binds to a shop (or grows into a merchant domain).

## Example

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

$fulfillment = Fulfillment::create(
    id: 'ful_01J...',
    orderId: 'ord_01J...',
    lines: [
        new FulfillmentLine('line-1', 'sku-123', 2),
    ],
);

$fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
$fulfillment->fulfillLine('line-1', 1);
// status: PartiallyFulfilled
$fulfillment->fulfillLine('line-1', 1);
// status: Fulfilled
```

Every transition is guarded by `canTransitionTo()`. Invalid transitions throw `InvalidTransitionException`; controllers and Eloquent observers do not own business rules.

## Boundaries and persistence

The `Domain` layer contains enums, entities, value objects, events, and repository contracts. `Application` contains use-case services. `Infrastructure` contains the Laravel service provider, Eloquent models, repositories, and migrations. A host application may replace the Eloquent repository with another adapter by rebinding `FulfillmentRepository`.

The archive table stores a JSON snapshot and metadata so records can disappear from normal operational queries without being destroyed. Use `ArchiveService::archive()` and `ArchiveService::restore()` for deep archival and recovery. A purge operation is deliberately not included: purge is destructive and should be an explicit, application-specific retention job.

### Optimistic concurrency

`Fulfillment` aggregates carry a `version` column that guards concurrent updates. A
write only succeeds when the stored version matches the version the aggregate was
loaded with; otherwise `StaleAggregateException` is thrown and the caller reloads
and retries. Repeated saves of the same in-memory aggregate advance the version.

### Archiving in practice

The host application decides when and what to archive. A typical use is to hide a
record from operational queries (for example, a fulfilled order older than the
retention window) while keeping a snapshot for analytics and audit:

```php
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;

$archiver = app(ArchiveService::class);

$archiver->archive(
    type: 'fulfillment',
    id: $fulfillment->id(),
    snapshot: $fulfillment->toArray(),
    reason: 'retention window passed',
    archivedBy: 'scheduled-job',
);

// Later, when the record needs to become visible again:
$archiver->restore(type: 'fulfillment', id: $fulfillment->id());
```

`ArchiveService::archive()` stores the snapshot and metadata, it never deletes the
source record; hiding it from operational queries is the host application's job.

## Status model

The package includes explicit transition graphs for `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`, `ShipmentStatus`, `ReturnStatus`, and `ProductAvailabilityStatus`. These are intentionally separate types even when values such as `pending`, `cancelled`, or `completed` look similar.

## Quality gates

```bash
composer test
composer analyse
```

PHP 8.3+ and Laravel 13 are supported. The core domain has no dependency on controllers, requests, facades, or Eloquent.

### Running tests inside the host application (current setup)

The package is developed as a path-repository dependency, so it reuses the host
application's test runner. From the host project root:

```bash
vendor/bin/phpunit --bootstrap vendor/autoload.php packages/yeod/commerce-lifecycle/tests/Unit
```

> Requires `pdo_sqlite` — if it is not enabled in `php.ini`, prepend
> `php -d extension=pdo_sqlite` to the command.

### Running tests standalone (after extraction to GitHub)

Once the package is extracted into its own repository, install its dependencies and run:

```bash
composer install
composer test
```

The package declares `ext-pdo_sqlite` in its `require-dev`, so `composer install`
validates that the SQLite PDO driver is available. If your PHP lacks it, enable it
(e.g. `php -d extension=pdo_sqlite`) before installing dev dependencies.
