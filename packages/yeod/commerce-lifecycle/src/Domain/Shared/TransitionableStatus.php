<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Shared;

/**
 * Contract implemented by domain status enums.
 */
interface TransitionableStatus
{
    /**
     * Determine whether the status may move to the target status.
     */
    public function canTransitionTo(self $target): bool;

    /**
     * Determine whether the status is terminal in its bounded context.
     */
    public function isFinal(): bool;
}
