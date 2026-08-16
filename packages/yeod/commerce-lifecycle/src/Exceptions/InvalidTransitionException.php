<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Exceptions;

use UnitEnum;

/**
 * Thrown when a domain object receives a transition that violates its graph.
 */
final class InvalidTransitionException extends CommerceLifecycleException
{
    public static function from(UnitEnum $from, UnitEnum $to): self
    {
        return new self(
            sprintf(
                'Transition from "%s" to "%s" is not allowed.',
                $from->name,
                $to->name,
            )
        );
    }
}