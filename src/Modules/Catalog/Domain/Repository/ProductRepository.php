<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Domain\Repository;

use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\Exception\ProductNotFound;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;

/**
 * Write side port for the product aggregate.
 *
 * The interface lives in the Domain, the Eloquent implementation lives in
 * Infrastructure. Read models are NOT served from here: use a query handler.
 */
interface ProductRepository
{
    /**
     * Generate the identity of a product that does not exist yet.
     */
    public function nextIdentity(): ProductId;

    /**
     * Load a product by identity.
     *
     * @param  ProductId  $id  Identity to look up.
     *
     * @throws ProductNotFound When no product has that identity.
     */
    public function get(ProductId $id): Product;

    /**
     * Find a product by SKU.
     *
     * @param  Sku  $sku  SKU to look up.
     * @return Product|null The product, or `null` when the SKU is free.
     */
    public function findBySku(Sku $sku): ?Product;

    /**
     * Determine whether a SKU is already taken.
     *
     * @param  Sku  $sku  SKU to check.
     */
    public function existsWithSku(Sku $sku): bool;

    /**
     * Persist the aggregate and publish the events it recorded.
     *
     * @param  Product  $product  Aggregate to store.
     */
    public function save(Product $product): void;
}
