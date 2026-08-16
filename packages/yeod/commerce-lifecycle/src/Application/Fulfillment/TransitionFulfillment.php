<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Application\Fulfillment;

use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

/**
 * Application use case for a guarded fulfillment transition.
 */
final readonly class TransitionFulfillment
{
    public function __construct(
        private FulfillmentRepository $repository,
        private Dispatcher $events,
    ) {}

    /**
     * @throws InvalidArgumentException When the fulfillment does not exist.
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
            $this->events->dispatch($event->eventName(), $event);
        }
    }
}
