<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application\Fulfillment;

use Yeod\CommerceLifecycle\Application\Authorizer;
use Yeod\CommerceLifecycle\Domain\Events\DomainEventDispatcher;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentRepository;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;
use Yeod\CommerceLifecycle\Exceptions\NotAuthorizedException;

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
        private ?Authorizer $authorizer = null,
    ) {}

    /**
     * Apply a guarded transition and dispatch any emitted domain events only
     * after the aggregate has been persisted.
     *
     * @throws InvalidArgumentException|InvalidTransitionException When the fulfillment does not exist or the transition is invalid.
     * @throws NotAuthorizedException When the configured authorizer denies the transition.
     */
    public function execute(string $fulfillmentId, FulfillmentStatus $target): void
    {
        $fulfillment = $this->repository->find($fulfillmentId);
        if ($fulfillment === null) {
            throw new InvalidArgumentException(sprintf('Fulfillment "%s" was not found.', $fulfillmentId));
        }

        $this->authorize('transition', 'fulfillment');

        $fulfillment->changeStatus($target);
        $this->repository->save($fulfillment);

        foreach ($fulfillment->releaseEvents() as $event) {
            $this->events->dispatch($event);
        }
    }

    /**
     * Guard the operation through the configured authorizer port. A null
     * authorizer (framework-free mode) skips the check; when a concrete
     * authorizer is bound it must grant the action explicitly (the package
     * default is the fail-closed {@see DenyAllAuthorizer}).
     */
    private function authorize(string $action, string $resourceType): void
    {
        if ($this->authorizer !== null && ! $this->authorizer->can($action, $resourceType)) {
            throw new NotAuthorizedException(
                sprintf('Not authorized to %s %s records.', $action, $resourceType)
            );
        }
    }
}
