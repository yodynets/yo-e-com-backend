<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Event;

/**
 * Port used by Infrastructure to publish domain events outside the aggregate.
 *
 * The Domain depends on this interface only; the Laravel implementation lives in
 * `Yeod\Shared\Infrastructure\Event`.
 */
interface DomainEventDispatcher
{
    /**
     * Publish the given events in the order they were recorded.
     *
     * @param  list<DomainEvent>  $events  Events released by an aggregate root.
     */
    public function dispatch(array $events): void;
}
