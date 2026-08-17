# Architecture notes

## Bounded contexts

The package is a reusable commerce-lifecycle kernel, not an all-in-one Order aggregate. A consuming application should keep its own `Orders`, `Payments`, `Catalog`, `Inventory`, and `Customer` contexts and translate their events at integration boundaries.

`Fulfillment` is the central operational context in this package. `FulfillmentStatus` is an aggregate-level state derived from line quantities; it should not be copied into an Order row as a second source of truth.

## Why `canTransitionTo()` belongs on the domain type

A status transition is a business invariant. Keeping the transition graph next to the enum makes it discoverable, testable, and reusable from commands, jobs, imports, and APIs. It also avoids controllers slowly becoming a second domain model.

`canTransitionTo()` intentionally takes only `self` (the same status type) and is **not**
part of the shared `TransitionableStatus` interface. A status may only ever move towards
another status of the same context; passing a status from another axis is a hard
`TypeError`, so cross-context mixing is rejected by the language, not by convention.

## Archiving

Archiving is orthogonal to the business lifecycle. A fulfilled order can be active or archived; a cancelled order can be active or archived. The archive snapshot supports analytics and audit use cases. Restore is possible while purge remains outside the package.

The package only provides the mechanism — `ArchiveService::archive()` / `restore()`
persist a JSON snapshot into the `commerce_archives` table. It never deletes the
source record. The decision of **when** and **what** to archive belongs to the host
application. Typical triggers:

- Legal/privacy requirements (a record must disappear from operational queries).
- Retention windows (fulfilled orders older than N years).
- Operational hygiene (removing noise from the active read model).

To use it, resolve `ArchiveService` from the container and pass the
`FulfillmentSnapshot::from($fulfillment)` snapshot plus a reason, exactly as
shown in the README example.

## Extension points

- Rebind repository contracts to a document store or another SQL adapter.
- Add domain events to an outbox implementation.
- Add application policies for who may transition a status.
- Add project-specific statuses by creating a new enum in the consuming bounded context rather than modifying a global god-enum.
