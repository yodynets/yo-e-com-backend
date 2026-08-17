<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Domain\Events;

/**
 * Port for dispatching domain events to the infrastructure/integration layer.
 */
interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;
}
