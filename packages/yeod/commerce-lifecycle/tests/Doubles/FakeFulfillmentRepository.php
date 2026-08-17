<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Doubles;

use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;

/**
 * @internal Test double implementing the fulfillment persistence port.
 */
final class FakeFulfillmentRepository implements FulfillmentRepository
{
    public function __construct(private ?Fulfillment $stored = null) {}

    public function find(string $id): ?Fulfillment
    {
        return $this->stored !== null && $this->stored->id() === $id ? $this->stored : null;
    }

    public function save(Fulfillment $fulfillment): void
    {
        $this->stored = $fulfillment;
    }
}
