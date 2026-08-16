<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Fulfillment;

use DateTimeImmutable;
use Yeod\CommerceLifecycle\Contracts\DomainEvent;

/**
 * Domain event emitted when a fulfillment changes status.
 */
final readonly class FulfillmentStatusChanged implements DomainEvent
{
    public function __construct(
        public string $fulfillmentId,
        public FulfillmentStatus $from,
        public FulfillmentStatus $to,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    /** Return the stable event name used by an integration bus or outbox. */
    public function eventName(): string { return 'commerce.fulfillment.status_changed'; }

    /** Return a serializable representation of the status change. */
    public function payload(): array
    {
        return [
            'fulfillment_id' => $this->fulfillmentId,
            'from'           => $this->from->value,
            'to'             => $this->to->value,
            'occurred_at'    => $this->occurredAt()->format(DATE_ATOM),
        ];
    }

    /** Return the time the status change happened. */
    public function occurredAt(): DateTimeImmutable { return $this->occurredAt; }
}
