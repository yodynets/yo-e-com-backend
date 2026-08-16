<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Application\Fulfillment;

use InvalidArgumentException;
use Yeod\CommerceLifecycle\Contracts\DomainEventDispatcher;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

/**
 * Application use case for a guarded fulfillment transition.
 *
 * The repository persists the aggregate in its own transaction; domain events
 * are dispatched only after a successful save.
 */
final readonly class TransitionFulfillment
{
    public function __construct(
        private FulfillmentRepository $repository,
        private DomainEventDispatcher $events,
    ) {}

    /**
     * Apply a guarded transition and dispatch any emitted domain events only
     * after the aggregate has been persisted.
     *
     * @throws InvalidArgumentException|InvalidTransitionException When the fulfillment does not exist.
     */
    public function execute(string $fulfillmentId, FulfillmentStatus $target): void
    {
        $fulfillment = $this->repository->find($fulfillmentId);
        if ($fulfillment === null) {
            throw new InvalidArgumentException(sprintf('Fulfillment "%s" was not found.', $fulfillmentId));
        }

        $fulfillment->changeStatus($target);
        $this->repository->save($fulfillment);

        foreach ($fulfillment->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }
}