<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persistence model for a fulfillment line.
 */
final class FulfillmentLineModel extends Model
{
    public    $incrementing = false;
    public    $timestamps   = false;
    protected $keyType      = 'string';
    protected $table        = 'commerce_fulfillment_lines';
    protected $fillable     = ['id', 'fulfillment_id', 'sku', 'ordered_quantity', 'fulfilled_quantity'];

    /** The fulfillment aggregate this line belongs to. */
    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(FulfillmentModel::class, 'fulfillment_id');
    }

    protected function casts(): array
    {
        return [
            'ordered_quantity'   => 'integer',
            'fulfilled_quantity' => 'integer',
        ];
    }
}
