<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application;

/**
 * Port for authorizing operations performed by the application layer.
 *
 * The consuming application provides a concrete implementation and rebinds
 * the interface in the container (see CommerceLifecycleServiceProvider).
 */
interface Authorizer
{
    public function can(string $action, string $resourceType): bool;
}
