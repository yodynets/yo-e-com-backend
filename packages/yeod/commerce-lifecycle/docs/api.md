# API reference

This document covers every public API of the `yeod/commerce-lifecycle` package
with runnable-style code examples. All examples assume the consuming application
has the package registered via its service provider.

---

## Status enums

Six status axes are modelled as **separate** PHP enums. Each implements
`TransitionableStatus` (which only declares `isFinal()`) and exposes its own
transition graph via `canTransitionTo(self $target)`.

Because the argument is typed as `self`, passing a status from another axis is a
**hard `TypeError`** — the language rejects cross-context mixing at the call site.

### OrderStatus

```php
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;

// Initial state
$current = OrderStatus::Pending;

// Transitions
$current->canTransitionTo(OrderStatus::AwaitingPayment);  // true
$current->canTransitionTo(OrderStatus::Cancelled);        // true
$current->canTransitionTo(OrderStatus::Completed);        // false — must go through Shipped

// Terminal states
OrderStatus::Completed->isFinal();  // true
OrderStatus::Cancelled->isFinal();  // true
OrderStatus::Pending->isFinal();    // false

// Full transition graph
OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::AwaitingFulfillment); // true
OrderStatus::Shipped->canTransitionTo(OrderStatus::Completed);                   // true
OrderStatus::Completed->canTransitionTo(OrderStatus::Refunded);                  // true
```

### PaymentStatus

```php
use Yeod\CommerceLifecycle\Domain\Payment\PaymentStatus;

PaymentStatus::Pending->canTransitionTo(PaymentStatus::Authorized); // true
PaymentStatus::Authorized->canTransitionTo(PaymentStatus::Captured); // true
PaymentStatus::Captured->canTransitionTo(PaymentStatus::PartiallyRefunded); // true
PaymentStatus::PartiallyRefunded->canTransitionTo(PaymentStatus::Refunded); // true

// Terminal
PaymentStatus::Refunded->isFinal();  // true
PaymentStatus::Voided->isFinal();    // true
```

### FulfillmentStatus

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

FulfillmentStatus::Scheduled->canTransitionTo(FulfillmentStatus::Unfulfilled); // true
FulfillmentStatus::Unfulfilled->canTransitionTo(FulfillmentStatus::PartiallyFulfilled); // true
FulfillmentStatus::OnHold->canTransitionTo(FulfillmentStatus::Cancelled); // true

// Terminal
FulfillmentStatus::Fulfilled->isFinal();  // true
FulfillmentStatus::Cancelled->isFinal();  // true
```

### ShipmentStatus

```php
use Yeod\CommerceLifecycle\Domain\Shipment\ShipmentStatus;

ShipmentStatus::LabelCreated->canTransitionTo(ShipmentStatus::AwaitingPickup); // true
ShipmentStatus::AwaitingPickup->canTransitionTo(ShipmentStatus::InTransit);    // true
ShipmentStatus::InTransit->canTransitionTo(ShipmentStatus::Delivered);         // true
ShipmentStatus::DeliveryFailed->canTransitionTo(ShipmentStatus::InTransit);    // true — retry
```

### ReturnStatus

```php
use Yeod\CommerceLifecycle\Domain\ReturnFlow\ReturnStatus;

ReturnStatus::Requested->canTransitionTo(ReturnStatus::Approved);        // true
ReturnStatus::Approved->canTransitionTo(ReturnStatus::LabelIssued);      // true
ReturnStatus::InTransit->canTransitionTo(ReturnStatus::Received);        // true
ReturnStatus::Inspecting->canTransitionTo(ReturnStatus::Accepted);       // true
ReturnStatus::Accepted->canTransitionTo(ReturnStatus::Refunded);         // true
```

### ProductAvailabilityStatus

```php
use Yeod\CommerceLifecycle\Domain\Catalog\ProductAvailabilityStatus;

ProductAvailabilityStatus::Draft->canTransitionTo(ProductAvailabilityStatus::Available); // true
ProductAvailabilityStatus::Available->canTransitionTo(ProductAvailabilityStatus::Discontinued); // true
ProductAvailabilityStatus::Discontinued->canTransitionTo(ProductAvailabilityStatus::Archived);  // true
ProductAvailabilityStatus::Archived->canTransitionTo(ProductAvailabilityStatus::Draft);         // true — revival

ProductAvailabilityStatus::Available->isSellable();  // true
ProductAvailabilityStatus::Draft->isSellable();      // false
```

---

## Fulfillment aggregate

The `Fulfillment` aggregate manages a set of `FulfillmentLine` items and
derives its own status from their quantities.

### Creating a fulfillment

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;

$fulfillment = Fulfillment::create(
    id: 'ful_01J',
    orderId: 'ord_01J',
    lines: [
        new FulfillmentLine(id: 'line-1', sku: 'sku-123', orderedQuantity: 2),
        new FulfillmentLine(id: 'line-2', sku: 'sku-456', orderedQuantity: 1),
    ],
    metadata: ['source' => 'web'],
);
```

### Fulfilling lines (status derivation)

```php
// Status is derived from line quantities
$fulfillment->fulfillLine('line-1', 1);
// status → PartiallyFulfilled  (line-1: 1/2 fulfilled, line-2: 0/1)

$fulfillment->fulfillLine('line-1', 1);
$fulfillment->fulfillLine('line-2', 1);
// status → Fulfilled  (all lines fully fulfilled)
```

### Explicit status transitions

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

$fulfillment = Fulfillment::create('ful-2', 'ord-2', [
    new FulfillmentLine('line-1', 'sku-1', 1),
]);

$fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
$fulfillment->changeStatus(FulfillmentStatus::OnHold);

// InvalidTransitionException is thrown when the graph forbids the move
try {
    $fulfillment->changeStatus(FulfillmentStatus::Fulfilled);
    // Fulfilled → OnHold is not allowed
} catch (InvalidTransitionException $e) {
    echo $e->getMessage(); // Transition from "OnHold" to "Fulfilled" is not allowed.
}
```

### Reconstituting from persistence

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;

$fulfillment = Fulfillment::reconstitute(
    id: 'ful_01J',
    orderId: 'ord_01J',
    status: FulfillmentStatus::PartiallyFulfilled,
    lines: [
        new FulfillmentLine('line-1', 'sku-123', 2, fulfilledQuantity: 1),
    ],
    metadata: ['source' => 'web'],
    createdAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
);
```

### Reading aggregate data

```php
$fulfillment->id();              // 'ful_01J'
$fulfillment->orderId();         // 'ord_01J'
$fulfillment->status();          // FulfillmentStatus::PartiallyFulfilled
$fulfillment->metadata();        // ['source' => 'web']
$fulfillment->createdAt();       // DateTimeImmutable
$fulfillment->lines();           // list<FulfillmentLine>
$fulfillment->toArray();         // serializable array
```

### Domain events

```php
// After a status change, pending events can be released
foreach ($fulfillment->releaseEvents() as $event) {
    echo $event->eventName();  // 'commerce.fulfillment.status_changed'
    echo $event->payload()['to'];  // e.g. 'fulfilled'
    echo $event->occurredAt()->format(DATE_ATOM);
}
```

---

## FulfillmentLine

Immutable value object representing a single line within a fulfillment.

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;

$line = new FulfillmentLine(
    id: 'line-1',
    sku: 'sku-123',
    orderedQuantity: 5,
    fulfilledQuantity: 2,  // optional, defaults to 0
);

$line->id();                // 'line-1'
$line->sku();               // 'sku-123'
$line->orderedQuantity();   // 5
$line->fulfilledQuantity(); // 2
$line->isFullyFulfilled();  // false (2 < 5)
$line->toArray();           // ['id' => 'line-1', 'sku' => 'sku-123', …]

$line->fulfill(3);          // now fulfilledQuantity = 5
$line->isFullyFulfilled();  // true
```

---

## FulfillmentRepository (contract)

Persistence port that the infrastructure layer implements. You may swap it
by rebinding the interface in the container.

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;

$repository = app(FulfillmentRepository::class);

$fulfillment = $repository->find('ful_01J'); // ?Fulfillment
$repository->save($fulfillment);             // persists aggregate + lines
```

---

## TransitionFulfillment (use case)

Application service that loads a fulfillment, applies a guarded transition,
persists the aggregate, and dispatches the resulting domain events.

```php
use Yeod\CommerceLifecycle\Application\Fulfillment\TransitionFulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

$useCase = app(TransitionFulfillment::class);

$useCase->execute('ful_01J', FulfillmentStatus::Fulfilled);
// loads → changeStatus → save → dispatch events
```

---

## Archiving

### ArchiveService

```php
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;

$archiver = app(ArchiveService::class);

// Deep-archive a record, keeping a snapshot for audit
$archiver->archive(
    type: 'fulfillment',
    id: 'ful_01J',
    snapshot: $fulfillment->toArray(),
    reason: 'retention window passed',
    archivedBy: 'scheduled-job',
    storageLocation: 'analytics-db',  // marker of the external store (a DB name, a JSON list, ...)
);

// Make the record visible again
$archiver->restore(type: 'fulfillment', id: 'ful_01J');

// Read the stored snapshot back, or null when not archived
$snapshot = $archiver->findSnapshot('fulfillment', 'ful_01J');

// Ask whether a record currently has an archived snapshot
$archived = $archiver->isArchived('fulfillment', 'ful_01J');
```

### ArchiveRepository (contract)

```php
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

$repository = app(ArchiveRepository::class);

$repository->archive('order', 'ord-01', ['total' => 100], 'customer request', 'user-1');
$repository->restore('order', 'ord-01');
$repository->findSnapshot('order', 'ord-01'); // ?array — stored snapshot
$repository->isArchived('order', 'ord-01');   // bool
```

---

## Domain events

```php
use Yeod\CommerceLifecycle\Contracts\DomainEvent;

// Implement this interface for other domain events
interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
    public function eventName(): string;      // stable name for bus/outbox
    public function payload(): array;         // serializable payload
}
```

The package ships one concrete event:

```php
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatusChanged;

$event = new FulfillmentStatusChanged(
    fulfillmentId: 'ful_01J',
    from: FulfillmentStatus::Unfulfilled,
    to: FulfillmentStatus::Fulfilled,
);

$event->eventName();       // 'commerce.fulfillment.status_changed'
$event->payload();         // ['fulfillment_id' => 'ful_01J', 'from' => 'unfulfilled', 'to' => 'fulfilled', …]
$event->occurredAt();      // DateTimeImmutable
```

---

## Exceptions

```php
use Yeod\CommerceLifecycle\Exceptions\CommerceLifecycleException;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

// Catch all package business exceptions with one type
try {
    // … domain operation …
} catch (CommerceLifecycleException $e) {
    // handle
}

// Create an exception manually
$e = InvalidTransitionException::from(
    FulfillmentStatus::Fulfilled,
    FulfillmentStatus::Unfulfilled,
);
echo $e->getMessage(); // 'Transition from "Fulfilled" to "Unfulfilled" is not allowed.'
```

---

## Cross-domain logic

The status axes are intentionally isolated — a `FulfillmentStatus` can never be
passed to `OrderStatus::canTransitionTo()` (the language rejects it with a
`TypeError`). This is a deliberate design decision.

When cross-domain logic is needed (for example: "is the order ready to be
shipped — meaning payment is captured AND fulfillment is not on hold"), do **not**
weaken the domain contract. Instead, write an explicit application-level method
that works with the concrete types:

```php
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;
use Yeod\CommerceLifecycle\Domain\Payment\PaymentStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

class OrderReadinessService
{
    /**
     * Determine whether an order is ready for shipment.
     *
     * This is an application-level decision, not a domain invariant.
     * Each status axis is consulted individually; no cross-typed transition
     * call is ever made.
     */
    public function isReadyToShip(
        OrderStatus $orderStatus,
        PaymentStatus $paymentStatus,
        FulfillmentStatus $fulfillmentStatus,
    ): bool {
        return $orderStatus === OrderStatus::AwaitingFulfillment
            && $paymentStatus === PaymentStatus::Captured
            && $fulfillmentStatus !== FulfillmentStatus::OnHold
            && $fulfillmentStatus !== FulfillmentStatus::Cancelled;
    }
}
```

The rule is: **compare values (===), never ask one axis to transition into
another**.

---

## Service provider

The package registers its own `CommerceLifecycleServiceProvider` which:

- Merges the package config (`config/commerce-lifecycle.php`).
- Binds `FulfillmentRepository` to `EloquentFulfillmentRepository`.
- Binds `ArchiveRepository` to `EloquentArchiveRepository`.

Publishable resources:

```bash
php artisan vendor:publish --tag=commerce-lifecycle-config
php artisan vendor:publish --tag=commerce-lifecycle-migrations
php artisan migrate
```

---

## Installation

```bash
composer require yeod/commerce-lifecycle
php artisan vendor:publish --tag=commerce-lifecycle-migrations
php artisan migrate
```