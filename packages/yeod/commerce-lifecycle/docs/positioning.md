# Positioning: why this package exists separately

This document explains how the package relates to a full e-commerce application
and why it is kept as a standalone library instead of being embedded into one
particular shop's codebase.

## What the package is

It is a **lifecycle kernel**, not an e-commerce application. It models the *states
and transitions* of operational records — order progress, payment movement,
fulfillment, shipment, returns, catalog availability — and provides a guarded
`Fulfillment` aggregate plus a deep-archive mechanism.

It deliberately does **not** model the shop's own entities: no `orders`,
`products`, `suppliers`, `warehouses`, `customers`, or `order_items`. Those belong
to the host application (or, in the future, to a separate merchant domain).

## How it binds to a real shop

The host application keeps its own tables and links them by plain identifiers:

```
orders (host)                         commerce_fulfillments (package)
  id: bigint  |----------------------> order_id: string (e.g. "123")
  status: OrderStatus                  status: FulfillmentStatus
  ...                                  ...
```

- `commerce_fulfillments.order_id` holds the host's order id as a string.
- `commerce_fulfillment_lines.sku` holds whatever the host uses as product key:
  an internal id, the 1C `ref` uuid, or the `code`. The package never resolves it.

This keeps the domain testable with zero Laravel dependencies and free of any
particular shop's schema (`suppliers`, `units`, `1C references`).

## Rules of the boundary

- Domain/Application layers know **only ids and statuses**, never Eloquent models.
- The host decides *what* to archive and *where* (`storage_location` marker).
- A status from one axis can never be passed into another axis — the language
  rejects it (`TypeError`).

## If someone wants to reuse it as a full shop domain

Say a team wants to embed this lifecycle directly into a merchant/backend and use
the same statuses as their own order fields, not as a separate fulfillment store.
That is supported without touching the package internals:

1. **Keep the package as a dependency** — do not copy it into the project. This
   keeps future fixes/upgrades in one place.
2. **Create a merchant module in the host** (e.g. `src/Modules/Merchant/`):
   - `Order`, `OrderItem` entities that own the host's tables (`orders`,
     `order_items`).
   - Eloquent models + `casts` mapping `status` to `OrderStatus` (or keep raw
     strings + validation).
3. **Compose, don't copy**:
   - Build a `Fulfillment` from the host's order via `Fulfillment::create()` with
     the same id/sku mapping.
   - Dispatch `FulfillmentStatusChanged` events into your own outbox/bus.
4. **Archive at the host level**: call `ArchiveService::archive()` with your own
   `storage_location` marker when a record should leave the active read model.

If a whole-team agreement exists that the lifecycle should become a merchant-wide
domain inside the package itself (move `orders`, `order_items`, etc. into the
package), do it as a **new major version with a migration plan**, not incrementally
in patch releases.

## Summary

The package models the *operation* of commerce records and is intentionally
separated from the *shape* of any single shop. That separation is what makes it
reusable — for a shop, for a logistics service, or for an analytics archive.