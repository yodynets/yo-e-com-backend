<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Event;

/**
 * Marks the single entry point of a consistency boundary.
 *
 * Only aggregate roots are loaded and saved by repositories, and only they are
 * allowed to record domain events. Use the `RecordsDomainEvents` trait for the
 * default implementation.
 */
interface AggregateRoot
{
    /**
     * Return every recorded event and clear the internal buffer.
     *
     * Called by the repository (or the unit of work) right after the aggregate
     * has been persisted, so events are only published on a successful write.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array;

    /**
     * Determine whether the aggregate has events waiting to be released.
     */
    public function hasRecordedEvents(): bool;
}
