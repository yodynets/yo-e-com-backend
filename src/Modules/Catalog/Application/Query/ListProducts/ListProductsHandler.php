<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Query\ListProducts;

use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\ReadModel\ProductReadModel;
use Yeod\Shared\Application\Bus\Query;
use Yeod\Shared\Application\Bus\QueryHandler;
use Yeod\Shared\Application\DTO\Page;

/**
 * Handles {@see ListProductsQuery}.
 *
 * @implements QueryHandler<ListProductsQuery>
 */
final readonly class ListProductsHandler implements QueryHandler
{
    /**
     * @param  ProductReadModel  $products  Read side port of the catalog.
     */
    public function __construct(private ProductReadModel $products) {}

    /**
     * Resolve the query.
     *
     * @param  ListProductsQuery  $query  Query to resolve.
     * @return Page<ProductDto> Page of read models.
     */
    public function handle(Query $query): Page
    {
        return $this->products->paginate($query->pagination, $query->search, $query->activeOnly);
    }
}
