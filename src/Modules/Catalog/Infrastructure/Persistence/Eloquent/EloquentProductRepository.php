<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use DateMalformedStringException;
use Random\RandomException;
use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\Exception\ProductNotFound;
use Yeod\Modules\Catalog\Domain\Repository\ProductRepository;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Yeod\Shared\Domain\Event\DomainEventDispatcher;

/**
 * Eloquent implementation of {@see ProductRepository}.
 *
 * `save()` writes the row and then publishes the events the aggregate recorded,
 * so listeners only ever see changes that were actually committed (the command bus
 * wraps the whole use case in a transaction).
 */
final readonly class EloquentProductRepository implements ProductRepository
{
    /**
     * @param  ProductMapper  $mapper  Row to aggregate translator.
     * @param  DomainEventDispatcher  $events  Publisher for released domain events.
     */
    public function __construct(
        private ProductMapper $mapper,
        private DomainEventDispatcher $events,
    ) {}

    /**
     * {@inheritDoc}
     * @throws RandomException
     */
    public function nextIdentity(): ProductId
    {
        return ProductId::generate();
    }

    /**
     * {@inheritDoc}
     * @throws DateMalformedStringException
     */
    public function get(ProductId $id): Product
    {
        $model = ProductModel::query()->find($id->value);

        if (! $model instanceof ProductModel) {
            throw ProductNotFound::withId($id);
        }

        return $this->mapper->toAggregate($model);
    }

    /**
     * {@inheritDoc}
     * @throws DateMalformedStringException
     */
    public function findBySku(Sku $sku): ?Product
    {
        $model = ProductModel::query()->where('sku', $sku->value)->first();

        return $model instanceof ProductModel ? $this->mapper->toAggregate($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function existsWithSku(Sku $sku): bool
    {
        return ProductModel::query()->where('sku', $sku->value)->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function save(Product $product): void
    {
        $attributes = $this->mapper->toAttributes($product);

        ProductModel::query()->updateOrCreate(
            ['id' => $attributes['id']],
            $attributes,
        );

        $this->events->dispatch($product->releaseEvents());
    }
}
