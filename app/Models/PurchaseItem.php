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
 * @property-read \App\Models\Product|null $products
 * @property-read \App\Models\Purchase|null $purchases
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem query()
 * @mixin \Eloquent
 */
class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity', 'unit_cost', 'subtotal',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:4',
    ];

    public function purchases(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function products(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
