<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Fulfillment;

/**
 * Persistence port for the fulfillment aggregate.
 */
interface FulfillmentRepository
{
    public function find(string $id): ?Fulfillment;

    public function save(Fulfillment $fulfillment): void;
}
