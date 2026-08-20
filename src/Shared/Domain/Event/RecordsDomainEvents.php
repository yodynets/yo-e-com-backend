<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Event;

/**
 * Default `AggregateRoot` implementation of the event buffer.
 *
 * @see AggregateRoot
 */
trait RecordsDomainEvents
{
    /**
     * Events recorded since the aggregate was loaded.
     *
     * @var list<DomainEvent>
     */
    private array $recordedEvents = [];

    /**
     * Return every recorded event and clear the internal buffer.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    /**
     * Determine whether the aggregate has events waiting to be released.
     */
    public function hasRecordedEvents(): bool
    {
        return $this->recordedEvents !== [];
    }

    /**
     * Buffer an event raised by a behaviour method of the aggregate.
     *
     * @param  DomainEvent  $event  Event describing what just happened.
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
