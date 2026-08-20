<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Contracts;

use Yeod\Modules\Catalog\Contracts\DTO\ProductSnapshot;

/**
 * Public API of the Catalog module.
 *
 * Other modules depend on this interface and nothing else from Catalog. Direct
 * Eloquent relations to catalog tables (`belongsTo(ProductModel::class)`) are
 * forbidden and blocked by Deptrac.
 *
 * ```php
 * // Inside the Orders module:
 * $product = $this->catalog->findProduct($productId)
 *     ?? throw OrderLineRejected::unknownProduct($productId);
 * ```
 */
interface CatalogModule
{
    /**
     * Find a product by identity.
     *
     * @param  string  $productId  Product identity as a string.
     * @return ProductSnapshot|null Snapshot, or `null` when the product does not exist.
     */
    public function findProduct(string $productId): ?ProductSnapshot;

    /**
     * Determine whether a product exists and may currently be sold.
     *
     * @param  string  $productId  Product identity as a string.
     */
    public function isSellable(string $productId): bool;
}
