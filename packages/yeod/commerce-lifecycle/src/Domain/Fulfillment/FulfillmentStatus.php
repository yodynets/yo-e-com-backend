<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Fulfillment;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum FulfillmentStatus: string implements TransitionableStatus
{
    case Scheduled          = 'scheduled';
    case Unfulfilled        = 'unfulfilled';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled          = 'fulfilled';
    case OnHold             = 'on_hold';
    case Cancelled          = 'cancelled';

    /**
     * @param  FulfillmentStatus  $target
     *
     * @return bool
     */
    public function canTransitionTo(TransitionableStatus $target): bool
    {
        return match ($this) {
            self::Scheduled => in_array($target, [self::Unfulfilled, self::OnHold, self::Cancelled], true),
            self::Unfulfilled, self::PartiallyFulfilled => in_array(
                $target,
                [self::PartiallyFulfilled, self::Fulfilled, self::OnHold, self::Cancelled],
                true
            ),
            self::OnHold => in_array(
                $target,
                [self::Unfulfilled, self::PartiallyFulfilled, self::Fulfilled, self::Cancelled],
                true
            ),
            self::Fulfilled, self::Cancelled => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Fulfilled, self::Cancelled], true);
    }
}
