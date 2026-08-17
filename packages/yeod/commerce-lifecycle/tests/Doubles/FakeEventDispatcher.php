<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Doubles;

use Yeod\CommerceLifecycle\Domain\Events\DomainEvent;
use Yeod\CommerceLifecycle\Domain\Events\DomainEventDispatcher;

/**
 * @internal Test double that records dispatched domain events.
 */
final class FakeEventDispatcher implements DomainEventDispatcher
{
    /** @var list<DomainEvent> */
    public array $dispatched = [];

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatched[] = $event;
    }
}
