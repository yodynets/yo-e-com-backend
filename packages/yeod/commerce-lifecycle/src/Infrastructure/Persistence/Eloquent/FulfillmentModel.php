<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

/**
 * Persistence model only. Do not place domain transition rules here.
 */
final class FulfillmentModel extends Model
{
    public    $incrementing = false;
    protected $keyType      = 'string';
    protected $guarded      = [];
    protected $table        = 'commerce_fulfillments';

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
        ];
    }
}
