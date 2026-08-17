<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Facades\DB;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Exceptions\StaleAggregateException;

/**
 * Eloquent adapter for the domain repository port.
 *
 * Optimistic concurrency control: every write is guarded by the aggregate's
 * version. A save only succeeds when the stored version still matches the
 * version the aggregate was loaded with; otherwise a `StaleAggregateException`
 * is thrown so the caller can reload and retry.
 */
final class EloquentFulfillmentRepository implements FulfillmentRepository
{
    /** Find a fulfillment aggregate by id, or null when it does not exist. */
    public function find(string $id): ?Fulfillment
    {
        $model = FulfillmentModel::query()->with('lines')->find($id);
        if ($model === null) {
            return null;
        }

        return Fulfillment::reconstitute(
            id       : (string) $model->getKey(),
            orderId  : (string) $model->order_id,
            status   : $model->status,
            lines    : array_values(
                $model->lines
                    ->map(static fn (FulfillmentLineModel $line): FulfillmentLine => new FulfillmentLine(
                        id               : (string) $line->id,
                        sku              : (string) $line->sku,
                        orderedQuantity  : (int) $line->ordered_quantity,
                        fulfilledQuantity: (int) $line->fulfilled_quantity,
                    ))
                    ->all()
            ),
            metadata : (array) ($model->metadata ?? []),
            createdAt: $model->created_at?->toImmutable(),
            version  : (int) $model->version,
        );
    }

    /**
     * Persist the aggregate and its lines atomically, guarding against
     * concurrent modifications via an optimistic version check.
     *
     * The aggregate's in-memory version is only advanced after the transaction
     * has committed, so a failed write (for example, a constraint violation in
     * {@see replaceLines()}) can never desynchronize the in-memory aggregate
     * from the persisted row — a bug that otherwise surfaces later as a bogus
     * `StaleAggregateException` with no real concurrency.
     *
     * @throws \Throwable
     */
    public function save(Fulfillment $fulfillment): void
    {
        $persistedVersion = DB::transaction(function () use ($fulfillment): int {
            $updated = FulfillmentModel::query()
                ->whereKey($fulfillment->id())
                ->where('version', $fulfillment->version())
                ->update([
                    'status' => $fulfillment->status()->value,
                    'metadata' => $fulfillment->metadata(),
                    'version' => $fulfillment->version() + 1,
                ]);

            if ($updated === 1) {
                $this->replaceLines($fulfillment->id(), $fulfillment->lines());

                return $fulfillment->version() + 1;
            }

            // Zero rows: the record is either absent or the version is stale.
            // Distinguish explicitly so a genuine insert succeeds and a
            // concurrent write raises the domain exception instead of an opaque
            // duplicate-key error (the old TOCTOU exists()/insert() race).
            if (FulfillmentModel::query()->whereKey($fulfillment->id())->lockForUpdate()->exists()) {
                throw new StaleAggregateException(
                    sprintf('Fulfillment "%s" was modified concurrently.', $fulfillment->id())
                );
            }

            $this->insert($fulfillment);

            return $fulfillment->version();
        });

        // Mutate the aggregate only after a successful commit.
        while ($fulfillment->version() < $persistedVersion) {
            $fulfillment->bumpVersion();
        }
    }

    private function insert(Fulfillment $fulfillment): void
    {
        FulfillmentModel::query()->create([
            'id' => $fulfillment->id(),
            'order_id' => $fulfillment->orderId(),
            'status' => $fulfillment->status()->value,
            'metadata' => $fulfillment->metadata(),
            'created_at' => $fulfillment->createdAt(),
            'version' => $fulfillment->version(),
        ]);

        $this->replaceLines($fulfillment->id(), $fulfillment->lines());
    }

    /**
     * Upsert the persisted lines of a fulfillment aggregate and drop any lines
     * that are no longer part of the aggregate.
     *
     * @param  list<FulfillmentLine>  $lines
     */
    private function replaceLines(string $fulfillmentId, array $lines): void
    {
        $payload = array_map(
            static fn (FulfillmentLine $line): array => [
                'id' => $line->id(),
                'fulfillment_id' => $fulfillmentId,
                'sku' => $line->sku(),
                'ordered_quantity' => $line->orderedQuantity(),
                'fulfilled_quantity' => $line->fulfilledQuantity(),
            ],
            $lines,
        );

        FulfillmentLineModel::query()->upsert(
            $payload,
            ['fulfillment_id', 'id'],
            ['sku', 'ordered_quantity', 'fulfilled_quantity'],
        );

        $retainedIds = array_map(static fn (FulfillmentLine $line): string => $line->id(), $lines);

        FulfillmentLineModel::query()
            ->where('fulfillment_id', $fulfillmentId)
            ->whereNotIn('id', $retainedIds)
            ->delete();
    }
}
