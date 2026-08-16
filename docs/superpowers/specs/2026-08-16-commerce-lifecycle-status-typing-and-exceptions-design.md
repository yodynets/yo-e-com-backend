# Design — Status typing isolation & domain exceptions for `yeod/commerce-lifecycle`

**Date:** 2026-08-16
**Status:** Proposed (awaiting review)
**Scope:** `packages/yeod/commerce-lifecycle` only

## Problem statement

The package models six **separate** status axes — `OrderStatus`, `PaymentStatus`,
`FulfillmentStatus`, `ShipmentStatus`, `ReturnStatus`, `ProductAvailabilityStatus` —
as distinct enum types on purpose (see README: *"These are intentionally separate
types even when values look similar"*).

Today the shared interface `TransitionableStatus` declares:

```php
public function canTransitionTo(self $target): bool;
```

Because the method lives on the shared interface, each enum is forced (by PHP
contravariance) to accept a broad `TransitionableStatus $target`. This allows a
programmer to accidentally ask a status from one context about a transition to a
status from a *different* context:

```php
FulfillmentStatus::Unfulfilled->canTransitionTo(PaymentStatus::Captured); // silently false
```

The mix is not rejected by the language — it is only hidden as a "not allowed
transition" instead of a compile-time/type error. This is exactly the class of bug
the separated enums were meant to prevent.

Additionally, the package has a single business exception (`TransitionException
extends DomainException`) with no common base that a consuming application could
`catch` once.

## Goals

1. Make cross-context status mixing **impossible at the PHP type level**, not just
   by convention.
2. Provide a single, catchable **domain exception base** for the package's business
   rules, keeping YAGNI in mind (no per-context exception explosion).
3. Keep existing calls intact — the only real `canTransitionTo` call site is
   `Fulfillment::changeStatus()`, which passes a concrete `FulfillmentStatus`.

## Non-goals

- No per-context exception hierarchy (`OrderStatusException`, `FulfillmentException`, …).
- No generic/template interface (PHP has no generics for such contracts).
- No cross-domain comparison API (if needed later, add an explicit use-case method,
  do not weaken the domain contract).

## Design

### 1. Typing — move `canTransitionTo` out of the interface

`TransitionableStatus` becomes a **marker + shared contract** interface that only
declares `isFinal()`:

```php
interface TransitionableStatus
{
    public function isFinal(): bool;
}
```

Each of the six status enums keeps its own transition graph with a **self-typed**
signature:

```php
enum FulfillmentStatus: string implements TransitionableStatus
{
    public function canTransitionTo(self $target): bool
    {
        return match ($this) { /* … */ };
    }

    public function isFinal(): bool { /* … */ }
}
```

Now `FulfillmentStatus::Unfulfilled->canTransitionTo(PaymentStatus::Captured)` is a
**fatal type error** — the language enforces context isolation.

**Compatibility:** the only in-package call site is
`Fulfillment::changeStatus(FulfillmentStatus $target)` → `$this->status->canTransitionTo($target)`
where `$this->status` is a concrete `FulfillmentStatus`. Verified: no code holds a
`TransitionableStatus` variable and calls `canTransitionTo`. No package code breaks.

### 2. Domain exceptions — single base, YAGNI

Add a common base exception for the whole package:

```php
namespace Yeod\CommerceLifecycle\Exceptions;

abstract class CommerceLifecycleException extends \Exception {}
```

Rename the existing business exception and move it under `Exceptions\` next to the
base, extending it:

```php
namespace Yeod\CommerceLifecycle\Exceptions;

final class InvalidTransitionException extends CommerceLifecycleException
{
    public static function from(UnitEnum $from, UnitEnum $to): self { /* … */ }
}
```

> The current class `TransitionException` is renamed to `InvalidTransitionException`
> (a more explicit name) and moved from `Domain/Shared/` to `Exceptions/`, keeping
> its `from()` factory. This is a **decided** outcome, not an open question.

`InvalidArgumentException` in `Fulfillment`/`FulfillmentLine` stays **standard SPL** —
it signals invalid input from the caller, not a business rule. It is deliberately not
converted into a domain exception.

## Testing

New PHPUnit tests in `packages/yeod/commerce-lifecycle/tests/Unit/` (pure domain, no
Laravel):

1. **Transition graph tests** — per context, assert the happy paths and that every
   disallowed transition returns `false` (or throws) for `OrderStatus`, `PaymentStatus`,
   `FulfillmentStatus`, `ShipmentStatus`, `ReturnStatus`, `ProductAvailabilityStatus`.
2. **Context isolation tests** — document via reflection/usage that passing a foreign
   enum cannot represent a valid transition; the language (after change 1) forbids it.
3. **Exception tests** — a forbidden transition throws `InvalidTransitionException`
   (extends `CommerceLifecycleException`), and `catch (CommerceLifecycleException)`
   catches it.

## Files touched

- `packages/yeod/commerce-lifecycle/src/Domain/Shared/TransitionableStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Order/OrderStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Payment/PaymentStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Fulfillment/FulfillmentStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Shipment/ShipmentStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/ReturnFlow/ReturnStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Catalog/ProductAvailabilityStatus.php`
- `packages/yeod/commerce-lifecycle/src/Domain/Shared/TransitionException.php` → renamed to `InvalidTransitionException`, moved to `Exceptions/`
- new: `packages/yeod/commerce-lifecycle/src/Exceptions/CommerceLifecycleException.php`
- new: `packages/yeod/commerce-lifecycle/src/Exceptions/InvalidTransitionException.php`
- new: `packages/yeod/commerce-lifecycle/tests/Unit/*` (graph + isolation + exception tests)