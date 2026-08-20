<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Query\GetProduct;

use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\ReadModel\ProductReadModel;
use Yeod\Shared\Application\Bus\Query;
use Yeod\Shared\Application\Bus\QueryHandler;

/**
 * Handles {@see GetProductQuery}.
 *
 * @implements QueryHandler<GetProductQuery>
 */
final readonly class GetProductHandler implements QueryHandler
{
    /**
     * @param  ProductReadModel  $products  Read side port of the catalog.
     */
    public function __construct(private ProductReadModel $products) {}

    /**
     * Resolve the query.
     *
     * @param  GetProductQuery  $query  Query to resolve.
     * @return ProductDto|null Read model, or `null` when the product does not exist.
     */
    public function handle(Query $query): ?ProductDto
    {
        return $this->products->findById($query->productId);
    }
}
