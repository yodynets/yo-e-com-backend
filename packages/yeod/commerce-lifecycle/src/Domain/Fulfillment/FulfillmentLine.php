<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Fulfillment;

use InvalidArgumentException;

/**
 * A quantity-bearing line inside a fulfillment aggregate.
 */
final class FulfillmentLine
{
    private int $fulfilledQuantity = 0;

    public function __construct(
        private readonly string $id,
        private readonly string $sku,
        private readonly int $orderedQuantity,
        int $fulfilledQuantity = 0,
    ) {
        if ($id === '' || $sku === '') {
            throw new InvalidArgumentException('A fulfillment line id and SKU are required.');
        }
        if ($orderedQuantity < 1 || $fulfilledQuantity < 0 || $fulfilledQuantity > $orderedQuantity) {
            throw new InvalidArgumentException('Fulfillment quantities are invalid.');
        }
        $this->fulfilledQuantity = $fulfilledQuantity;
    }

    /** Return the unique line identifier. */
    public function id(): string { return $this->id; }

    /** Return the SKU this line refers to. */
    public function sku(): string { return $this->sku; }

    /** Return the total quantity ordered for this line. */
    public function orderedQuantity(): int { return $this->orderedQuantity; }

    /** Return the quantity already fulfilled for this line. */
    public function fulfilledQuantity(): int { return $this->fulfilledQuantity; }

    /** Determine whether the whole ordered quantity has been fulfilled. */
    public function isFullyFulfilled(): bool { return $this->fulfilledQuantity === $this->orderedQuantity; }

    /**
     * Increase the fulfilled quantity while preserving aggregate invariants.
     */
    public function fulfill(int $quantity): void
    {
        if ($quantity < 1 || $this->fulfilledQuantity + $quantity > $this->orderedQuantity) {
            throw new InvalidArgumentException('Fulfilled quantity exceeds the ordered quantity.');
        }
        $this->fulfilledQuantity += $quantity;
    }

    /**
     * @return array{id: string, sku: string, ordered_quantity: int, fulfilled_quantity: int}
     */
    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'sku'                => $this->sku,
            'ordered_quantity'   => $this->orderedQuantity,
            'fulfilled_quantity' => $this->fulfilledQuantity,
        ];
    }
}
