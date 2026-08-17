<?php

/**
 * @author  Yevhen Odynets
 *
 * @since   2026-08-16
 */

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Contracts;

interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;
}
