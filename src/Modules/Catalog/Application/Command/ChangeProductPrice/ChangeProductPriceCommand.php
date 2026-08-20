<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Command\ChangeProductPrice;

use Yeod\Shared\Application\Bus\Command;

/**
 * Set a new selling price for an existing product.
 */
final readonly class ChangeProductPriceCommand implements Command
{
    /**
     * @param  string  $productId  Identity of the product to reprice.
     * @param  string  $price  New price as a decimal string.
     * @param  string  $currency  ISO 4217 currency code, must match the current price.
     */
    public function __construct(
        public string $productId,
        public string $price,
        public string $currency,
    ) {}
}
