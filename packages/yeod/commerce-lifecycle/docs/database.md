# Database schema and design rationale

This document describes the exact tables the package creates and **why each piece
exists the way it does**. It is the source of truth alongside
`database/migrations/2026_01_01_000000_create_commerce_lifecycle_tables.php`; if
the migration and this document ever disagree, the migration wins.

> The package manages **only three tables**:
> `commerce_fulfillments`, `commerce_fulfillment_lines`, `commerce_archives`.
>
> It deliberately does **not** own the host's `orders`, `products`, `suppliers` or
> `customers` tables. The host holds those and links to the package by plain ids
> (`order_id`, `sku`). See [positioning.md](positioning.md).

---

## Overview (entity–relationship)

```
      commerce_fulfillments                    commerce_archives
   ┌──────────────────────────┐              ┌──────────────────────────┐
   │ id            string  PK │  ◄──┐        │ id             bigint PK │
   │ order_id      string     │     │lines   │ archivable_type string   │
   │ status        string     │     │(FK)    │ archivable_id   string   │
   │ metadata      json     ↳ │     │        │ reason          string?  │
   │ version       int      ↳ │     │        │ archived_by     string?  │
   │ created_at    timestamp  │     │        │ storage_location string? │
   │ updated_at    timestamp  │     │        │ snapshot        json     │
   └──────────────────────────┘     │        │ archived_at     timestamp│
        │            ▲              │        │ restored_at     timestamp?
        │ order_id   │              │        └──────────────────────────┘
        │ (host id,  │    ┌─────────┴─────────────┐        │
        │  no FK)    │    │ commerce_fulfillment_lines      │ unique(archivable_type,
        │            │    ├───────────────────────┤        │ archivable_id)
   host's `orders`  │    │ id      string PK     │        │
   (not installed)  │    │ fulfillment_id string  │◄───────┘
                    │    │ sku     string         │
                    │    │ ordered_quantity   uint│
                    │    │ fulfilled_quantity uint│
                    └────┼───────────────────────┘
```

- `commerce_fulfillments` **1 – N** `commerce_fulfillment_lines`
  (`fulfillment_id` FK, `cascadeOnDelete`).
- `commerce_fulfillments.order_id` is **not** an FK — it points to a row in the
  host's `orders` table that the package does not own.
- `commerce_archives` is a standalone audit store with a unique
  `(archivable_type, archivable_id)` per record.

---

## Table: `commerce_fulfillments`

One row = one **fulfillment aggregate**.

| Column | Type | Nullable | Index | Notes |
|---|---|---|---|---|
| `id` | `string` | no | **PK** | Host-chosen id (e.g. `ful_01J…`); not auto-increment. |
| `order_id` | `string` | no | idx | Bare reference to the host's order; **no FK** by design. |
| `status` | `string` | no | idx | Raw string of `FulfillmentStatus` value (see rationale). |
| `metadata` | `json` | **yes** | — | Arbitrary host metadata (`array<string,mixed>`). |
| `version` | `unsigned int` | no | — | Optimistic-concurrency guard; default `1`. |
| `created_at` | `timestamp` | no | — | Set from the aggregate's own clock. |
| `updated_at` | `timestamp` | no | — | Eloquent-managed. |

Indexes: PK on `id`, index on `order_id`, index on `status`.

## Table: `commerce_fulfillment_lines`

One row per line inside a fulfillment aggregate.

| Column | Type | Nullable | Index | Notes |
|---|---|---|---|---|
| `id` | `string` | no | **PK** | Line id chosen inside the aggregate (e.g. `line-1`). |
| `fulfillment_id` | `string` | no | idx | FK → `commerce_fulfillments.id`. |
| `sku` | `string` | no | idx | Host's product key (internal id, 1C `ref`, or `code`); **no FK**. |
| `ordered_quantity` | `unsigned int` | no | — | Total quantity ordered. |
| `fulfilled_quantity` | `unsigned int` | no | — | Quantity fulfilled; default `0`, always ≤ `ordered_quantity`. |

Constraints: PK on `id`; FK `fulfillment_id` → `commerce_fulfillments.id`
(`cascadeOnDelete`). No `created_at`/`updated_at`: lines are rewritable leaf rows
that always travel inside a save of their aggregate.

## Table: `commerce_archives`

A **deep snapshot** of a record that left (or may leave) the operational read model.
It **never deletes** the source record.

| Column | Type | Nullable | Index | Notes |
|---|---|---|---|---|
| `id` | `bigint` | no | **PK** | Local auto-increment (surrogate). |
| `archivable_type` | `string` | no | idx | Type tag chosen by the host (e.g. `fulfillment`). |
| `archivable_id` | `string` | no | idx | Id of the archived record, mirrored from the source id. |
| `reason` | `string` | yes | — | Why it was archived. |
| `archived_by` | `string` | yes | — | Who/what archived it (e.g. `scheduled-job`). |
| `storage_location` | `string` | yes | — | Marker where the snapshot is physically kept (e.g. analytics DB). Not interpreted by the package. |
| `snapshot` | `json` | no | — | Deep JSON snapshot (`toArray()` of the aggregate). |
| `archived_at` | `timestamp` | no | — | When it was archived. |
| `restored_at` | `timestamp` | yes | — | Set when `restore()` is called; `NULL` while active. |

Constraint: **unique `(archivable_type, archivable_id)`** — one *latest* snapshot per
record (`updateOrCreate` semantics).

---

## Why the schema looks like this

### 1. String primary keys, not auto-increment integers

`commerce_fulfillments.id`, `commerce_fulfillment_lines.id`, `archivable_id` are
host-chosen strings.

- The package is a kernel: the same fulfillment must be addressable from the host,
  from queues/jobs, and from logs by a stable, human-meaningful id.
- Distributed/sharded sources often cannot rely on central auto-increment.
- No `O`-zero/`1`-ell collisions that plague "looks-numeric" strings; ids are minted
  by the host, validated for non-emptiness in the domain.

### 2. `order_id` and `sku` are plain strings with no FK

- `orders`, `products`, … belong to the **host**, not to the package (see
  [positioning.md](positioning.md)). Adding an FK would force the package to own
  those tables or require their exact schema.
- The boundary holds: the domain knows **ids** (`order_id`, `sku`), never the host's
  Eloquent models.
- A composite/foreign key to a table that may not exist would break out-of-the-box
  installation for a shop that names its tables differently.

### 3. `status` is a `string`, and validation lives in the app enum

- A native DB `ENUM`/CHECK would make adding or renaming a status a **schema
  migration**; the package prefers that statuses evolve in the application enum
  (`FulfillmentStatus`) without locking the schema.
- Invalid stored values are rejected eagerly by `FulfillmentStatus::from()` when the
  aggregate is reconstituted — a bad row fails loudly instead of silently corrupting
  domain state.
- String values are stable wire/JSON representations (`unfulfilled`,
  `partially_fulfilled`, …), independent of PHP enum case names.

### 4. `version` + unsigned default `1` (optimistic concurrency)

The aggregate carries a `version`; writes use optimistic locking:

- `save()` **inserts** the first row at the aggregate's version;
- an **update** is issued with `WHERE id = ? AND version = <loaded version>` and, on
  success, stores `version + 1` and increments the aggregate's local version.
- If another writer already bumped the row, the `UPDATE` matches 0 rows →
  `StaleAggregateException` → the caller reloads and retries.

Rationale: `find` → mutate → `save` is a read-modify-write. Without a guard, two
concurrent fulfillers could silently overwrite each other (lost update). Optimistic
locking is the lightest correct tool here — no locks held across the mutation,
works across any transaction isolation level that reads committed data.

### 5. Fulfillment **N** Lines as separate tables

A fulfillment is an **aggregate with child lines**. They are persisted as their own
table so that quantity math (`ordered`, `fulfilled`) is queryable per line and
indexed by `sku` for reporting. Lines have no own timestamps because they are always
rewritten together with their aggregate (the repository deletes and re-creates them
in one transaction); keeping timestamps would add meaningless `updated_at` churn on
every partial fulfillment.

`cascadeOnDelete` guarantees no orphan lines when a fulfillment row is removed.

### 6. One aggregate, one query graph — eager-loading

The repository loads a fulfillment with `with('lines')` in a single query; no
lazy-loading surprises, no N+1. This is why the `fulfillment_id` FK is indexed.

### 7. `commerce_archives` — snapshot, not a soft-delete flag

- Archiving is **orthogonal** to the business statuses: a *fulfilled* or *cancelled*
  record can each be active or archived.
- The package stores a **deep JSON snapshot** because the operational rows may be
  truncated or the schema of the source record may evolve; the snapshot preserves
  the exact state at archive time for analytics/audit.
- `(archivable_type, archivable_id)` is **unique** because the archive keeps the
  *latest* snapshot per record (`updateOrCreate`), not a full history — history is
  the host's responsibility if it needs it.
- `restored_at` is nullable; `archive()` clears it, `restore()` sets it. Only
  `NULL` rows count as **active** in `isArchived()`. The row itself is retained so
  the audit trail survives even after restore.
- **No purge operation** ships deliberately: delete is destructive and should be an
  explicit, retention-policy-driven job owned by the host.

### 8. `metadata` and `snapshot` are JSON

Both are flexible holder-for-whatever-the-host-needs fields:

- `metadata` on a fulfillment stores non-domain annotations (e.g. carrier note).
- `snapshot` on an archive stores the aggregate's `toArray()`.

JSON keeps the package schema stable while the host-specific shape changes. It is
validated for serializability and size at the application boundary
(`ArchiveService`).

### 9. No timestamps on `commerce_archives`

Archive has explicit `archived_at`/`restored_at` domain semantics instead; Eloquent
auto `created_at`/`updated_at` are irrelevant for an append-mostly, host-controlled
audit store.

---

## Data integrity summary

| Invariant | Where enforced |
|---|---|
| `ordered_quantity ≥ 1`, `0 ≤ fulfilled ≤ ordered` | `FulfillmentLine` (domain) |
| line ids unique within an aggregate | `Fulfillment` constructor (domain) |
| status transitions legal | `FulfillmentStatus::canTransitionTo()` (domain) |
| concurrent writes do not silently clobber | `version` guard in repository |
| no orphan lines | FK `cascadeOnDelete` (DB) |
| exactly one *latest* archive snapshot per record | unique `(archivable_type, archivable_id)` (DB) |
| source record is never deleted by archiving | `ArchiveService` never issues a DELETE on the source |

---

## Schema evolution note

This is a **pre-release** package with a single create-migration. If you have
already run it on a live database and later add columns via a new package version,
you must ship an **additive** migration (e.g. `…_add_version_to_fulfillments`) —
never edit the create migration in place. Existing rows added before the `version`
feature should be backfilled as `version = 1` before the optimistic guard is
relied on.