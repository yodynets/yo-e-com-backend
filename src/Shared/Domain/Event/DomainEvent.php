<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Event;

use DateTimeImmutable;

/**
 * A fact that happened inside the Domain.
 *
 * Domain events are the only sanctioned way for one module to react to another
 * module's behaviour. Keep the payload primitive so the event can be queued,
 * serialised and replayed without depending on the publisher's classes.
 */
interface DomainEvent
{
    /**
     * Stable dot separated event name used for routing and storage.
     *
     * Example: `catalog.product.price_changed`.
     */
    public function eventName(): string;

    /**
     * Identifier of the aggregate that recorded the event.
     */
    public function aggregateId(): string;

    /**
     * Moment the event happened, in UTC.
     */
    public function occurredAt(): DateTimeImmutable;

    /**
     * Primitive only payload, safe to serialise to JSON.
     *
     * @return array<string, scalar|array<array-key, scalar|null>|null>
     */
    public function payload(): array;
}
