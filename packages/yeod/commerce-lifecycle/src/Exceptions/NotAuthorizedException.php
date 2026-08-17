<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

/**
 * Thrown when an application operation is not permitted by the configured
 * authorizer port.
 */
final class NotAuthorizedException extends CommerceLifecycleException {}
