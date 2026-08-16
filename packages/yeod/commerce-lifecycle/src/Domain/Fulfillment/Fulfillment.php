<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Fulfillment;

use DateTimeImmutable;
use InvalidArgumentException;
use Yeod\CommerceLifecycle\Contracts\DomainEvent;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

/**
 * Fulfillment aggregate. Its status is derived from line quantities and
 * remains independent of payment, shipment, and order statuses.
 */
final class Fulfillment
{
    /** @var array<string, FulfillmentLine> */
    private array $lines;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    /**
     * @param  list<FulfillmentLine>  $lines
     * @param  array<string, mixed>   $metadata
     */
    private function __construct(
        private readonly string $id,
        private readonly string $orderId,
        private FulfillmentStatus $status,
        array $lines,
        private readonly array $metadata = [],
        private readonly DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private int $version = 1
    ) {
        if ($id === '' || $orderId === '' || $lines === []) {
            throw new InvalidArgumentException('A fulfillment requires ids and at least one line.');
        }
        $this->lines = [];
        foreach ($lines as $line) {
            if (isset($this->lines[$line->id()])) {
                throw new InvalidArgumentException('Fulfillment line ids must be unique.');
            }
            $this->lines[$line->id()] = $line;
        }
    }

    /** Return the unique fulfillment id. */
    public function id(): string { return $this->id; }

    /**
     * Create a new fulfillment aggregate in the `Scheduled` state.
     *
     * @param  list<FulfillmentLine>  $lines
     * @param  array<string, mixed>   $metadata
     */
    public static function create(string $id, string $orderId, array $lines, array $metadata = []): self
    {
        return new self($id, $orderId, FulfillmentStatus::Scheduled, $lines, $metadata, version: 1);
    }

    /**
     * Reconstitute an aggregate from persistence without emitting events.
     *
     * @param  list<FulfillmentLine>  $lines
     * @param  array<string, mixed>   $metadata
     */
    public static function reconstitute(
        string $id,
        string $orderId,
        FulfillmentStatus $status,
        array $lines,
        array $metadata = [],
        ?DateTimeImmutable $createdAt = null,
        int $version = 1,
    ): self {
        return new self($id, $orderId, $status, $lines, $metadata, $createdAt ?? new DateTimeImmutable(), $version);
    }

    /** Return the order this fulfillment belongs to. */
    public function orderId(): string { return $this->orderId; }

    /** Return the current aggregate status. */
    public function status(): FulfillmentStatus { return $this->status; }

    /** @return array<string, mixed> */
    public function metadata(): array { return $this->metadata; }

    /**
     * Fulfill part or all of a line and recalculate the aggregate status.
     *
     * @throws InvalidArgumentException
     * @throws InvalidTransitionException
     */
    public function fulfillLine(string $lineId, int $quantity): void
    {
        if (! isset($this->lines[$lineId])) {
            throw new InvalidArgumentException(sprintf('Unknown fulfillment line "%s".', $lineId));
        }
        if ($this->status === FulfillmentStatus::Scheduled) {
            $this->changeStatus(FulfillmentStatus::Unfulfilled);
        }
        if ($this->status === FulfillmentStatus::OnHold) {
            throw new InvalidArgumentException('A fulfillment on hold cannot be fulfilled.');
        }
        $this->lines[$lineId]->fulfill($quantity);
        $hasFulfilled = false;
        $allFulfilled = true;
        foreach ($this->lines as $line) {
            $hasFulfilled = $hasFulfilled || $line->fulfilledQuantity() > 0;
            $allFulfilled = $allFulfilled && $line->isFullyFulfilled();
        }
        $target = match (true) {
            $allFulfilled => FulfillmentStatus::Fulfilled,
            $hasFulfilled => FulfillmentStatus::PartiallyFulfilled,
            default       => FulfillmentStatus::Unfulfilled,
        };
        if ($this->status !== $target) {
            $this->changeStatus($target);
        }
    }

    /**
     * Apply a guarded status transition.
     *
     * @throws InvalidTransitionException
     */
    public function changeStatus(FulfillmentStatus $target): void
    {
        if ($this->status === $target) {
            return;
        }
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidTransitionException::from($this->status, $target);
        }
        $from = $this->status;
        $this->status = $target;
        $this->domainEvents[] = new FulfillmentStatusChanged($this->id, $from, $target);
    }

    /**
     * Return and clear the pending domain events.
     *
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'order_id'   => $this->orderId,
            'status'     => $this->status->value,
            'metadata'   => $this->metadata,
            'created_at' => $this->createdAt()->format(DATE_ATOM),
            'lines'      => array_map(static fn(FulfillmentLine $line): array => $line->toArray(), $this->lines()),
        ];
    }

    /** Return the time the aggregate was created. */
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }

    /** Return the aggregate version used for optimistic concurrency control. */
    public function version(): int { return $this->version; }

    /**
     * Advance the local version after a successful persisted update so that
     * repeated saves without a reload remain coherent.
     */
    public function bumpVersion(): void
    {
        $this->version++;
    }

    /**
     * Return all fulfillment lines.
     *
     * @return list<FulfillmentLine>
     */
    public function lines(): array { return array_values($this->lines); }
}
