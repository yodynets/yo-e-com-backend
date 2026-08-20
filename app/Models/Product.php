<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \App\Models\Category|null $products
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseItem> $purchaseItems
 * @property-read int|null $purchase_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SaleItem> $saleItems
 * @property-read int|null $sale_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stock> $stocks
 * @property-read int|null $stocks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StocksAdjustments> $stocksAdjustments
 * @property-read int|null $stocks_adjustments_count
 * @property-read \App\Models\Unit|null $unit
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @mixin \Eloquent
 */
class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'category_id', 'unit_id',
        'cost_price', 'selling_price', 'image', 'description', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean', 'cost_price' => 'decimal:4', 'selling_price' => 'decimal:4',
    ];

    public function products(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stocksAdjustments(): HasMany
    {
        return $this->hasMany(StocksAdjustments::class);
    }

    public function totalStock()
    {
        return $this->stocks()->sum('quantity');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}
