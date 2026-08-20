<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Event;

use Illuminate\Contracts\Events\Dispatcher;
use Yeod\Shared\Domain\Event\DomainEvent;
use Yeod\Shared\Domain\Event\DomainEventDispatcher;

/**
 * Publishes domain events through Laravel's event dispatcher.
 *
 * Every event is dispatched twice: once under its class name (typed listeners)
 * and once under its `eventName()` (wildcard listeners such as
 * `catalog.product.*`), which is how other modules subscribe without importing
 * the publisher's classes.
 */
final readonly class LaravelDomainEventDispatcher implements DomainEventDispatcher
{
    /**
     * @param  Dispatcher  $events  Framework event dispatcher.
     */
    public function __construct(private Dispatcher $events) {}

    /**
     * {@inheritDoc}
     */
    public function dispatch(array $events): void
    {
        foreach ($events as $event) {
            $this->events->dispatch($event);
            $this->events->dispatch($event->eventName(), [$event]);
        }
    }

    /**
     * Publish the events released by an aggregate root.
     *
     * @param  DomainEvent  ...$events  Events to publish.
     */
    public function dispatchAll(DomainEvent ...$events): void
    {
        $this->dispatch(array_values($events));
    }
}
