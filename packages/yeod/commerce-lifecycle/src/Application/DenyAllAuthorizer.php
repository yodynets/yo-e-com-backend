<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application;

/**
 * Fail-closed authorizer that denies every action by default.
 *
 * This is the package default so an operator who has not configured an
 * authorizer does not silently end up with authorization disabled. Grant
 * explicit access by binding a concrete {@see Authorizer} implementation (or
 * opting into {@see AllowAllAuthorizer} for local development).
 */
final class DenyAllAuthorizer implements Authorizer
{
    public function can(string $action, string $resourceType): false
    {
        return false;
    }
}
