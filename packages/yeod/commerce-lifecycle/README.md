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

## Installation

```bash
composer require yeod/commerce-lifecycle
php artisan vendor:publish --tag=commerce-lifecycle-config
php artisan vendor:publish --tag=commerce-lifecycle-migrations
php artisan migrate
```

## Example

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Shared\TransitionException;

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

Every transition is guarded by `canTransitionTo()`. Invalid transitions throw `TransitionException`; controllers and Eloquent observers do not own business rules.

## Boundaries and persistence

The `Domain` layer contains enums, entities, value objects, events, and repository contracts. `Application` contains use-case services. `Infrastructure` contains the Laravel service provider, Eloquent models, repositories, and migrations. A host application may replace the Eloquent repository with another adapter by rebinding `FulfillmentRepository`.

The archive table stores a JSON snapshot and metadata so records can disappear from normal operational queries without being destroyed. Use `ArchiveService::archive()` and `ArchiveService::restore()` for deep archival and recovery. A purge operation is deliberately not included: purge is destructive and should be an explicit, application-specific retention job.

## Status model

The package includes explicit transition graphs for `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`, `ShipmentStatus`, `ReturnStatus`, and `ProductAvailabilityStatus`. These are intentionally separate types even when values such as `pending`, `cancelled`, or `completed` look similar.

## Quality gates

```bash
composer test
composer analyse
```

PHP 8.3+ and Laravel 13 are supported. The core domain has no dependency on controllers, requests, facades, or Eloquent.
