<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\Query\GetProduct\GetProductQuery;
use Yeod\Modules\Catalog\Application\Query\ListProducts\ListProductsQuery;
use Yeod\Modules\Catalog\Presentation\Http\Requests\CreateProductRequest;
use Yeod\Shared\Application\Bus\CommandBus;
use Yeod\Shared\Application\Bus\QueryBus;
use Yeod\Shared\Application\DTO\Page;
use Yeod\Shared\Application\DTO\Pagination;

/**
 * Thin HTTP adapter over the catalog use cases.
 *
 * The controller has no business logic: it validates, dispatches and serialises.
 * Any rule that appears here is a rule that belongs in the Domain.
 */
final readonly class ProductController
{
    /**
     * @param  CommandBus  $commands  Write side bus.
     * @param  QueryBus  $queries  Read side bus.
     */
    public function __construct(
        private CommandBus $commands,
        private QueryBus $queries,
    ) {}

    /**
     * List products.
     *
     * @param  Request  $request  Incoming request carrying pagination and filters.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Page<ProductDto> $page */
        $page = $this->queries->ask(new ListProductsQuery(
            pagination: new Pagination(
                page: max(1, $request->integer('page', 1)),
                perPage: min(Pagination::MAX_PER_PAGE, max(1, $request->integer('per_page', 25))),
            ),
            search: $request->string('search')->toString() ?: null,
            activeOnly: $request->boolean('active_only') ?: null,
        ));

        return new JsonResponse($page->toArray());
    }

    /**
     * Show one product.
     *
     * @param  string  $productId  Product identity from the route.
     */
    public function show(string $productId): JsonResponse
    {
        $product = $this->queries->ask(new GetProductQuery($productId));

        if (! $product instanceof ProductDto) {
            return new JsonResponse(['message' => 'Product not found.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $product->toArray()]);
    }

    /**
     * Create a product.
     *
     * @param  CreateProductRequest  $request  Validated creation payload.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $productId = $this->commands->dispatch($request->toCommand());

        return new JsonResponse(['data' => ['id' => $productId]], Response::HTTP_CREATED);
    }
}
