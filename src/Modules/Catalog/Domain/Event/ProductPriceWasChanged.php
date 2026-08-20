<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\Event;

use DateTimeImmutable;
use Yeod\Shared\Domain\Event\DomainEvent;

/**
 * The price of a product changed.
 *
 * Other modules (Orders, Inventory, a price history projector) subscribe to this
 * event instead of querying the catalogue tables directly.
 */
final readonly class ProductPriceWasChanged implements DomainEvent
{
    /**
     * @param  string  $productId  Identity of the product.
     * @param  int  $oldMinorAmount  Previous price in minor units.
     * @param  int  $newMinorAmount  New price in minor units.
     * @param  string  $currency  ISO 4217 currency code of both amounts.
     * @param  DateTimeImmutable  $occurredAt  Moment the price changed.
     */
    public function __construct(
        public string $productId,
        public int $oldMinorAmount,
        public int $newMinorAmount,
        public string $currency,
        public DateTimeImmutable $occurredAt,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function eventName(): string
    {
        return 'catalog.product.price_changed';
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
            'product_id'       => $this->productId,
            'old_minor_amount' => $this->oldMinorAmount,
            'new_minor_amount' => $this->newMinorAmount,
            'currency'         => $this->currency,
        ];
    }
}
