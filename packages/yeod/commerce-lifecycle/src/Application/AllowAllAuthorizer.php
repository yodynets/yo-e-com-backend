<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application;

/**
 * Explicit opt-in authorizer that grants every action.
 *
 * This is **not** the default — the package ships with fail-closed
 * {@see DenyAllAuthorizer} by default. Use `AllowAllAuthorizer` for local
 * development only; in production, bind a real {@see Authorizer} implementation
 * or rely on the default (which denies everything until a policy is configured).
 */
final class AllowAllAuthorizer implements Authorizer
{
    public function can(string $action, string $resourceType): bool
    {
        return true;
    }
}
