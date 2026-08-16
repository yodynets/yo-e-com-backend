<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

/**
 * Persistence model only. Do not place domain transition rules here.
 *
 * @property string                                $id
 * @property string                                $order_id
 * @property FulfillmentStatus                     $status
 * @property array<string, mixed>                  $metadata
 * @property int                                   $version
 * @property Carbon|null                           $created_at
 * @property Carbon|null                           $updated_at
 * @property Collection<int, FulfillmentLineModel> $lines
 */
final class FulfillmentModel extends Model
{
    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $table        = 'commerce_fulfillments';
    protected $fillable     = [
        'id',
        'order_id',
        'status',
        'metadata',
        'created_at',
        'updated_at',
        'version',
    ];

    /**
     * The fulfillment lines persisted for this aggregate.
     *
     * @return HasMany<FulfillmentLineModel, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(FulfillmentLineModel::class, 'fulfillment_id');
    }

    protected function casts(): array
    {
        return [
            'status'     => FulfillmentStatus::class,
            'metadata'   => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'version'    => 'integer',
        ];
    }
}