<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Facades\DB;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Throwable;

/**
 * Eloquent adapter for the domain repository port.
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
            status   : $model->status instanceof FulfillmentStatus
                ? $model->status
                : FulfillmentStatus::from(
                    (string)$model->status
                ),
            lines    : $model->lines->map(static fn(FulfillmentLineModel $line): FulfillmentLine => new FulfillmentLine(
                (string)$line->id,
                (string)$line->sku,
                (int)$line->ordered_quantity,
                (int)$line->fulfilled_quantity,
            ))->all(),
            metadata : (array)($model->metadata ?? []),
            createdAt: $model->created_at?->toImmutable(),
        );
    }

    /**
     * Persist the aggregate and its lines in a single transaction.
     *
     * @throws Throwable
     */
    public function save(Fulfillment $fulfillment): void
    {
        DB::transaction(static function () use ($fulfillment): void {
            $model = FulfillmentModel::query()->updateOrCreate(
                ['id' => $fulfillment->id()],
                [
                    'order_id'   => $fulfillment->orderId(),
                    'status'     => $fulfillment->status()->value,
                    'metadata'   => $fulfillment->metadata(),
                    'created_at' => $fulfillment->createdAt(),
                ],
            );
            $model->lines()->delete();
            foreach ($fulfillment->lines() as $line) {
                $model->lines()->create([
                    'id'                 => $line->id(),
                    'sku'                => $line->sku(),
                    'ordered_quantity'   => $line->orderedQuantity(),
                    'fulfilled_quantity' => $line->fulfilledQuantity(),
                ]);
            }
        });
    }
}
