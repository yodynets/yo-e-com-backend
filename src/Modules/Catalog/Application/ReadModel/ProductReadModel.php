<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\ReadModel;

use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Shared\Application\DTO\Page;
use Yeod\Shared\Application\DTO\Pagination;

/**
 * Read side port of the catalog.
 *
 * Queries bypass the aggregate on purpose: rebuilding entities to render a table
 * is wasteful, and the read side has different needs than the write side (CQRS).
 */
interface ProductReadModel
{
    /**
     * Find a single product by identity.
     *
     * @param  string  $productId  Product identity as a string.
     * @return ProductDto|null The read model, or `null` when not found.
     */
    public function findById(string $productId): ?ProductDto;

    /**
     * List products, optionally filtered by a search term.
     *
     * @param  Pagination  $pagination  Page request.
     * @param  string|null  $search  Term matched against SKU and name.
     * @param  bool|null  $activeOnly  When `true`, only sellable products are returned.
     * @return Page<ProductDto> Page of read models.
     */
    public function paginate(Pagination $pagination, ?string $search = null, ?bool $activeOnly = null): Page;
}
