<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Yeod\Modules\Catalog\Application\DTO\ProductDto;
use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

/**
 * Translates between the `catalog_products` row and the Product aggregate.
 *
 * Keeping the mapping in one class is what allows the Domain to stay ignorant of
 * the storage shape: rename a column here, not in the aggregate.
 */
final readonly class ProductMapper
{
    /**
     * Hydrate the aggregate from a database row.
     *
     * @param  ProductModel  $model  Row to convert.
     *
     * @throws DateMalformedStringException
     */
    public function toAggregate(ProductModel $model): Product
    {
        return Product::restore(
            id       : ProductId::fromString($model->id),
            sku      : Sku::fromString($model->sku),
            name     : $model->name,
            price    : Money::fromMinor($model->price_minor_amount, Currency::fromCode($model->currency)),
            active   : $model->active,
            createdAt: new DateTimeImmutable($model->created_at->toDateTimeString(), new DateTimeZone('UTC')),
        );
    }

    /**
     * Flatten the aggregate into a column map for insert or update.
     *
     * @param  Product  $product  Aggregate to convert.
     *
     * @return array<string, scalar> Column values.
     */
    public function toAttributes(Product $product): array
    {
        return [
            'id'                 => $product->id()->value,
            'sku'                => $product->sku()->value,
            'name'               => $product->name(),
            'price_minor_amount' => $product->price()->minorAmount,
            'currency'           => $product->price()->currency->value,
            'active'             => $product->isActive(),
            'created_at'         => $product->createdAt()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Project a database row straight into a read model, skipping the aggregate.
     *
     * @param  ProductModel  $model  Row to convert.
     */
    public function toDto(ProductModel $model): ProductDto
    {
        $money = Money::fromMinor($model->price_minor_amount, Currency::fromCode($model->currency));

        return new ProductDto(
            id              : $model->id,
            sku             : $model->sku,
            name            : $model->name,
            priceMinorAmount: $money->minorAmount,
            priceFormatted  : $money->toDecimalString(),
            currency        : $money->currency->value,
            active          : $model->active,
            createdAt       : $model->created_at->toAtomString(),
        );
    }
}
