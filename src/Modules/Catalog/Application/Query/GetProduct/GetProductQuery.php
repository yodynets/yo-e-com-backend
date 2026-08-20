<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Query\GetProduct;

use Yeod\Shared\Application\Bus\Query;

/**
 * Fetch a single product read model by identity.
 */
final readonly class GetProductQuery implements Query
{
    /**
     * @param  string  $productId  Identity of the product to fetch.
     */
    public function __construct(public string $productId) {}
}
