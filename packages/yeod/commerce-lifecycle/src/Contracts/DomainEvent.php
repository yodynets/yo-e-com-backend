<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Contracts;

use DateTimeImmutable;

/**
 * Marker contract for events emitted by the domain layer.
 */
interface DomainEvent
{
    /**
     * Return the event occurrence time.
     */
    public function occurredAt(): DateTimeImmutable;

    /**
     * Return a stable event name for an outbox or integration bus.
     */
    public function eventName(): string;

    /**
     * Return a serializable event payload.
     *
     * @return array<string, mixed>
     */
    public function payload(): array;
}
