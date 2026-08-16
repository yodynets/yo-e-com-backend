<?php

declare(strict_types = 1);

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
            id       : (string)$model->getKey(),
            orderId  : (string)$model->order_id,
            status   : $model->status,
            lines    : array_values(
                $model->lines
                    ->map(static fn(FulfillmentLineModel $line): FulfillmentLine => new FulfillmentLine(
                        id               : (string)$line->id,
                        sku              : (string)$line->sku,
                        orderedQuantity  : (int)$line->ordered_quantity,
                        fulfilledQuantity: (int)$line->fulfilled_quantity,
                    ))
                    ->all()
            ),
            metadata : (array)($model->metadata ?? []),
            createdAt: $model->created_at?->toImmutable(),
            version  : (int)$model->version,
        );
    }

    /**
     * Persist the aggregate and its lines atomically, guarding against
     * concurrent modifications via an optimistic version check.
     *
     * @throws \Throwable
     */
    public function save(Fulfillment $fulfillment): void
    {
        DB::transaction(function () use ($fulfillment): void {
            if (! FulfillmentModel::query()->whereKey($fulfillment->id())->exists()) {
                $this->insert($fulfillment);

                return;
            }

            $updated = FulfillmentModel::query()
                ->whereKey($fulfillment->id())
                ->where('version', $fulfillment->version())
                ->update([
                    'status'   => $fulfillment->status()->value,
                    'metadata' => $fulfillment->metadata(),
                    'version'  => $fulfillment->version() + 1,
                ]);

            if ($updated === 0) {
                throw new StaleAggregateException(
                    sprintf('Fulfillment "%s" was modified concurrently.', $fulfillment->id())
                );
            }

            $fulfillment->bumpVersion();
            $this->replaceLines($fulfillment->id(), $fulfillment->lines());
        });
    }

    private function insert(Fulfillment $fulfillment): void
    {
        FulfillmentModel::query()->create([
            'id'         => $fulfillment->id(),
            'order_id'   => $fulfillment->orderId(),
            'status'     => $fulfillment->status()->value,
            'metadata'   => $fulfillment->metadata(),
            'created_at' => $fulfillment->createdAt(),
            'version'    => $fulfillment->version(),
        ]);

        $this->replaceLines($fulfillment->id(), $fulfillment->lines());
    }

    /**
     * Replace the persisted lines of a fulfillment aggregate.
     *
     * @param  list<FulfillmentLine>  $lines
     */
    private function replaceLines(string $fulfillmentId, array $lines): void
    {
        FulfillmentLineModel::query()->where('fulfillment_id', $fulfillmentId)->delete();

        foreach ($lines as $line) {
            FulfillmentLineModel::query()->create([
                'id'                 => $line->id(),
                'fulfillment_id'     => $fulfillmentId,
                'sku'                => $line->sku(),
                'ordered_quantity'   => $line->orderedQuantity(),
                'fulfilled_quantity' => $line->fulfilledQuantity(),
            ]);
        }
    }
}