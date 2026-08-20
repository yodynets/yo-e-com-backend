<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Contracts\DTO;

/**
 * Immutable, primitive only view of a product shared with other modules.
 *
 * This class is part of the module's public API: changing it is a breaking change
 * for Orders, Payments and anything else that consumes the catalog.
 */
final readonly class ProductSnapshot
{
    /**
     * @param  string  $id  Product identity.
     * @param  string  $sku  Business identifier.
     * @param  string  $name  Display name.
     * @param  int  $priceMinorAmount  Price in minor units.
     * @param  string  $currency  ISO 4217 currency code.
     * @param  bool  $active  Whether the product may be sold.
     */
    public function __construct(
        public string $id,
        public string $sku,
        public string $name,
        public int $priceMinorAmount,
        public string $currency,
        public bool $active,
    ) {}
}
