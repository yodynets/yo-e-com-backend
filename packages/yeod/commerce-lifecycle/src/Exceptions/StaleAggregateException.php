<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

/**
 * Thrown when an aggregate cannot be persisted because it was modified
 * concurrently since it was last loaded.
 */
final class StaleAggregateException extends CommerceLifecycleException
{
}