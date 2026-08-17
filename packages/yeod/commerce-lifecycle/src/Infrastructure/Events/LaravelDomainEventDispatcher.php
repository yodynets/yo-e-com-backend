<?php

/**
 * @author  Yevhen Odynets
 *
 * @since   2026-08-16
 */

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Infrastructure\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Yeod\CommerceLifecycle\Contracts\DomainEvent;
use Yeod\CommerceLifecycle\Contracts\DomainEventDispatcher;

final readonly class LaravelDomainEventDispatcher implements DomainEventDispatcher
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function dispatch(DomainEvent $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
