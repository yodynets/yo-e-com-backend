<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Domain\Event;

use DateTimeImmutable;
use Yeod\Shared\Domain\Event\DomainEvent;

/**
 * A product entered the catalog.
 */
final readonly class ProductWasCreated implements DomainEvent
{
    /**
     * @param  string  $productId  Identity of the new product.
     * @param  string  $sku  SKU of the new product.
     * @param  string  $name  Name of the new product.
     * @param  int  $priceMinorAmount  Price in minor units.
     * @param  string  $currency  ISO 4217 currency code of the price.
     * @param  DateTimeImmutable  $occurredAt  Moment the product was created.
     */
    public function __construct(
        public string $productId,
        public string $sku,
        public string $name,
        public int $priceMinorAmount,
        public string $currency,
        public DateTimeImmutable $occurredAt,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function eventName(): string
    {
        return 'catalog.product.created';
    }

    /**
     * {@inheritDoc}
     */
    public function aggregateId(): string
    {
        return $this->productId;
    }

    /**
     * {@inheritDoc}
     */
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * {@inheritDoc}
     */
    public function payload(): array
    {
        return [
            'product_id' => $this->productId,
            'sku' => $this->sku,
            'name' => $this->name,
            'price_minor_amount' => $this->priceMinorAmount,
            'currency' => $this->currency,
        ];
    }
}
