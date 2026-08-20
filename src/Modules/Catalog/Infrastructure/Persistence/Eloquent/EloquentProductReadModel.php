<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Application\ReadModel\ProductReadModel;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Yeod\Shared\Application\DTO\Page;
use Yeod\Shared\Application\DTO\Pagination;

/**
 * Eloquent implementation of {@see ProductReadModel}.
 */
final readonly class EloquentProductReadModel implements ProductReadModel
{
    /**
     * @param  ProductMapper  $mapper  Row to DTO translator.
     */
    public function __construct(private ProductMapper $mapper) {}

    /**
     * {@inheritDoc}
     */
    public function findById(string $productId): ?ProductDto
    {
        $model = ProductModel::query()->find($productId);

        return $model instanceof ProductModel ? $this->mapper->toDto($model) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(Pagination $pagination, ?string $search = null, ?bool $activeOnly = null): Page
    {
        $query = ProductModel::query()
            ->when($activeOnly === true, static fn(Builder $builder): Builder => $builder->where('active', true))
            ->when(
                $search !== null && trim($search) !== '',
                static fn(Builder $builder): Builder => $builder->where(
                    static function (Builder $nested) use ($search): void {
                        $term = '%'.trim((string)$search).'%';
                        $nested->where('sku', 'like', $term)->orWhere('name', 'like', $term);
                    },
                ),
            );

        $total = (clone $query)->count();

        /** @var list<ProductDto> $items */
        $items = $query
            ->orderBy('name')
            ->offset($pagination->offset())
            ->limit($pagination->perPage)
            ->get()
            ->map(fn(ProductModel $model): ProductDto => $this->mapper->toDto($model))
            ->all();

        return new Page($items, $total, $pagination);
    }
}
