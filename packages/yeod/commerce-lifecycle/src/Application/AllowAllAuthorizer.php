<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application;

/**
 * Default no-op authorizer that grants every action.
 *
 * Replace it in production when authorization is required.
 */
final class AllowAllAuthorizer implements Authorizer
{
    public function can(string $action, string $resourceType): bool
    {
        return true;
    }
}
