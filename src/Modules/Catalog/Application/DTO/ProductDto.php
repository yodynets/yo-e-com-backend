<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\DTO;

use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Shared\Application\DTO\Arrayable;

/**
 * Read model of a product as exposed to the Presentation layer.
 *
 * Primitives only: neither the aggregate nor the Eloquent model ever leaves the
 * Application layer.
 */
final readonly class ProductDto implements Arrayable
{
    /**
     * @param  string  $id  Product identity.
     * @param  string  $sku  Business identifier.
     * @param  string  $name  Display name.
     * @param  int  $priceMinorAmount  Price in minor units.
     * @param  string  $priceFormatted  Price rendered as a decimal string.
     * @param  string  $currency  ISO 4217 currency code.
     * @param  bool  $active  Whether the product may be sold.
     * @param  string  $createdAt  Creation moment as an ISO 8601 string.
     */
    public function __construct(
        public string $id,
        public string $sku,
        public string $name,
        public int $priceMinorAmount,
        public string $priceFormatted,
        public string $currency,
        public bool $active,
        public string $createdAt,
    ) {}

    /**
     * Build the DTO from the aggregate.
     *
     * @param  Product  $product  Aggregate to project.
     */
    public static function fromAggregate(Product $product): self
    {
        return new self(
            id: $product->id()->value,
            sku: $product->sku()->value,
            name: $product->name(),
            priceMinorAmount: $product->price()->minorAmount,
            priceFormatted: $product->price()->toDecimalString(),
            currency: $product->price()->currency->value,
            active: $product->isActive(),
            createdAt: $product->createdAt()->format(DATE_ATOM),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'price_minor_amount' => $this->priceMinorAmount,
            'price' => $this->priceFormatted,
            'currency' => $this->currency,
            'active' => $this->active,
            'created_at' => $this->createdAt,
        ];
    }
}
