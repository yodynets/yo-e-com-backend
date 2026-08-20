<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Infrastructure\Api;

use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\Query\GetProduct\GetProductQuery;
use Yeod\Modules\Catalog\Contracts\CatalogModule;
use Yeod\Modules\Catalog\Contracts\DTO\ProductSnapshot;
use Yeod\Shared\Application\Bus\QueryBus;

/**
 * Adapter that fulfils the Catalog public API by asking the query bus.
 *
 * Cross module calls therefore reuse the same use cases as HTTP and Filament: no
 * second read path, no shortcut into the database.
 */
final readonly class CatalogModuleApi implements CatalogModule
{
    /**
     * @param  QueryBus  $queries  Bus used to reach the catalog read side.
     */
    public function __construct(private QueryBus $queries) {}

    /**
     * {@inheritDoc}
     */
    public function findProduct(string $productId): ?ProductSnapshot
    {
        $product = $this->queries->ask(new GetProductQuery($productId));

        if (! $product instanceof ProductDto) {
            return null;
        }

        return new ProductSnapshot(
            id: $product->id,
            sku: $product->sku,
            name: $product->name,
            priceMinorAmount: $product->priceMinorAmount,
            currency: $product->currency,
            active: $product->active,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isSellable(string $productId): bool
    {
        return $this->findProduct($productId)?->active === true;
    }
}
