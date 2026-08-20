<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Query\ListProducts;

use Yeod\Shared\Application\Bus\Query;
use Yeod\Shared\Application\DTO\Pagination;

/**
 * Fetch a page of product read models.
 */
final readonly class ListProductsQuery implements Query
{
    /**
     * @param  Pagination  $pagination  Page request.
     * @param  string|null  $search  Term matched against SKU and name.
     * @param  bool|null  $activeOnly  When `true`, only sellable products are returned.
     */
    public function __construct(
        public Pagination $pagination = new Pagination,
        public ?string $search = null,
        public ?bool $activeOnly = null,
    ) {}
}
