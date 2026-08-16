# Statuses reference

This document describes every status axis modelled by the package and the meaning
of each value. The separation between **order** and **fulfillment** statuses is based
on Locad's *"Fulfillment Status in E-commerce"* article
(https://www.golocad.com/fulfillment/fulfillment-status/).

## Why the axes are separate

An e-commerce record does not have a single "status". It has several independent
lifecycles that must not be collapsed into one field:

- **Order status** describes how far along the commercial order is and what the next
  customer or operator action is.
- **Payment status** describes money movement.
- **Fulfillment status** describes whether order lines are scheduled, unfulfilled,
  partially fulfilled, or fulfilled.
- **Shipment status** describes the physical package in transit.
- **Return status** describes the reverse-logistics flow.
- **Catalog availability** describes whether a product may appear in or be sold from
  the catalog.
- **Archive state** is orthogonal operational metadata; it is not a business status
  and does not delete the record.

Per Locad: *"An order status informs how far along the order is and primarily results
from customer action. On the contrary, fulfillment status informs a customer of an
order's processing and shipment statuses."* This is why `OrderStatus` and
`FulfillmentStatus` are intentionally distinct types even when some values look
similar (for example `cancelled`).

## OrderStatus

| Value | Meaning |
| --- | --- |
| `pending` | Order created; awaiting a next action. |
| `awaiting_payment` | Customer needs to complete payment. |
| `payment_failed` | The latest payment attempt failed; retry or cancel. |
| `awaiting_fulfillment` | Order placed but fulfillment has not started. |
| `awaiting_pickup` | Order ready for pickup at a physical location. |
| `shipped` | Order handed to a carrier. |
| `completed` | Order reached the customer; not final — may later transition to refunded. |
| `cancelled` | Order cancelled; terminal. |
| `refunded` | Money returned; terminal. |

## PaymentStatus

| Value | Meaning |
| --- | --- |
| `pending` | Payment created; no action yet. |
| `authorized` | Funds held but not captured. |
| `captured` | Funds collected. |
| `failed` | Payment could not be completed; terminal. |
| `partially_refunded` | Part of the captured amount returned. |
| `refunded` | Full amount returned; terminal. |
| `voided` | Authorization cancelled before capture; terminal. |

## FulfillmentStatus

Derived from line quantities: the aggregate recomputes its status from
`FulfillmentLine` quantities.

| Value | Meaning |
| --- | --- |
| `scheduled` | Fulfillment created but not started. |
| `unfulfilled` | Nothing shipped yet; awaiting processing. |
| `partially_fulfilled` | Some lines are fulfilled, not all. |
| `fulfilled` | All lines fulfilled; terminal. |
| `on_hold` | Paused, e.g. post-purchase/upsell hold. |
| `cancelled` | Fulfillment cancelled; terminal. |

## ShipmentStatus

| Value | Meaning |
| --- | --- |
| `label_created` | Carrier label generated; package not picked up. |
| `awaiting_pickup` | Carrier scheduled to collect the package. |
| `in_transit` | Package moving towards the destination. |
| `out_for_delivery` | Last-mile carrier is delivering today. |
| `delivered` | Package delivered; terminal. |
| `delivery_failed` | Delivery attempt failed; retry or return. |
| `returned_to_sender` | Package sent back; terminal. |
| `cancelled` | Shipment cancelled; terminal. |

## ReturnStatus

| Value | Meaning |
| --- | --- |
| `requested` | Customer requested a return. |
| `approved` | Return accepted; label may be issued. |
| `rejected` | Return not accepted; terminal. |
| `label_issued` | Return label provided to the customer. |
| `in_transit` | Package returning to the seller. |
| `received` | Package arrived at the warehouse. |
| `inspecting` | Quality control in progress. |
| `accepted` | Return accepted after inspection. |
| `partially_accepted` | Some items accepted, others not. |
| `refunded` | Money returned; terminal. |
| `replaced` | Replacement item sent; terminal. |
| `closed` | Return flow finished; terminal. |

## ProductAvailabilityStatus

| Value | Meaning |
| --- | --- |
| `draft` | Product not yet published. |
| `scheduled` | Product will become available at a future time. |
| `available` | Sellable; may appear in the catalog. |
| `temporarily_unavailable` | Out of stock but expected to return. |
| `discontinued` | Permanently removed from sale; terminal. |
| `archived` | Hidden from the catalog; can be revived to `draft`. |

## Archiving

Archiving is orthogonal to the business lifecycle: it is not a fake business status
and it never deletes the source record. The package provides the mechanism — a deep
JSON snapshot stored in the `commerce_archives` table via `ArchiveService` — but the
decision of when and what to archive belongs to the host application. A purge
operation is deliberately not included.

Each archive record may carry an optional `storage_location` marker (a plain string)
pointing to where the snapshot is physically kept — e.g. an analytics database name
or a JSON list of external stores. The package stores the marker and the snapshot,
it does not interpret the marker. Use `ArchiveService::findSnapshot()` to read a
snapshot back and `ArchiveService::isArchived()` to check whether a record is archived.

## Transition rules

- Every status exposes `canTransitionTo(self $target)` with a **self**-typed argument.
  A status from another axis cannot be passed — the language rejects it with a
  `TypeError`.
- Invalid transitions within the same axis throw `InvalidTransitionException`.
- Terminal states (`isFinal()`) cannot transition to anything else.
