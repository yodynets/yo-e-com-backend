<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Domain\Shared;

/**
 * Contract implemented by domain status enums.
 *
 * Only declares shared behaviour. Transition graphs are intentionally NOT part
 * of this contract: a status may only ever move towards another status of the
 * same context, so each enum exposes its own `canTransitionTo(self $target)`.
 */
interface TransitionableStatus
{
    /**
     * Determine whether the status is terminal in its bounded context.
     */
    public function isFinal(): bool;
}
