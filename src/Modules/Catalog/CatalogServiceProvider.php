<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog;

use Yeod\Modules\Catalog\Application\Command\ChangeProductPrice\ChangeProductPriceCommand;
use Yeod\Modules\Catalog\Application\Command\ChangeProductPrice\ChangeProductPriceHandler;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductHandler;
use Yeod\Modules\Catalog\Application\Query\GetProduct\GetProductHandler;
use Yeod\Modules\Catalog\Application\Query\GetProduct\GetProductQuery;
use Yeod\Modules\Catalog\Application\Query\ListProducts\ListProductsHandler;
use Yeod\Modules\Catalog\Application\Query\ListProducts\ListProductsQuery;
use Yeod\Modules\Catalog\Application\ReadModel\ProductReadModel;
use Yeod\Modules\Catalog\Contracts\CatalogModule;
use Yeod\Modules\Catalog\Domain\Repository\ProductRepository;
use Yeod\Modules\Catalog\Infrastructure\Api\CatalogModuleApi;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentProductReadModel;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\EloquentProductRepository;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource;
use Yeod\Shared\Infrastructure\Module\ModuleServiceProvider;

/**
 * Catalog module wiring.
 *
 * Reference implementation of the module convention: copy this file, rename it and
 * replace the maps. Everything the module needs is declared here and nowhere else.
 */
final class CatalogServiceProvider extends ModuleServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'catalog';
    }

    /**
     * {@inheritDoc}
     */
    protected function bindings(): array
    {
        return [
            ProductRepository::class => EloquentProductRepository::class,
            ProductReadModel::class  => EloquentProductReadModel::class,
            CatalogModule::class     => CatalogModuleApi::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function commandHandlers(): array
    {
        return [
            CreateProductCommand::class      => CreateProductHandler::class,
            ChangeProductPriceCommand::class => ChangeProductPriceHandler::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function queryHandlers(): array
    {
        return [
            GetProductQuery::class   => GetProductHandler::class,
            ListProductsQuery::class => ListProductsHandler::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function filamentResources(): array
    {
        return [
            ProductResource::class,
        ];
    }
}
