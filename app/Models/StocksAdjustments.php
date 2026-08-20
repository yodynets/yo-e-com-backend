<?php

/**
 * @author  Yevhen Odynets
 *
 * @since   2026-08-19
 */

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\User|null $adjustedBy
 * @property-read \App\Models\Product|null $products
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StocksAdjustments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StocksAdjustments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StocksAdjustments query()
 * @mixin \Eloquent
 */
class StocksAdjustments extends Model
{
    protected $fillable = [
        'product_id', 'warehouse_id', 'type', 'quantity', 'reason', 'adjusted_by',
    ];

    public function products(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
