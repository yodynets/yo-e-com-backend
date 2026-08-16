<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Order;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum OrderStatus: string implements TransitionableStatus
{
    case Pending             = 'pending';
    case AwaitingPayment     = 'awaiting_payment';
    case PaymentFailed       = 'payment_failed';
    case AwaitingFulfillment = 'awaiting_fulfillment';
    case AwaitingPickup      = 'awaiting_pickup';
    case Shipped             = 'shipped';
    case Completed           = 'completed';
    case Cancelled           = 'cancelled';
    case Refunded            = 'refunded';

    /**
     * @param  OrderStatus  $target
     *
     * @return bool
     */
    public function canTransitionTo(TransitionableStatus $target): bool
    {
        return match ($this) {
            self::Pending, self::PaymentFailed => in_array($target, [self::AwaitingPayment, self::Cancelled], true),
            self::AwaitingPayment => in_array(
                $target,
                [self::AwaitingFulfillment, self::PaymentFailed, self::Cancelled],
                true
            ),
            self::AwaitingFulfillment => in_array(
                $target,
                [self::AwaitingPickup, self::Shipped, self::Cancelled],
                true
            ),
            self::AwaitingPickup => in_array($target, [self::Shipped, self::Cancelled], true),
            self::Shipped => in_array($target, [self::Completed, self::Refunded], true),
            self::Completed => $target === self::Refunded,
            self::Cancelled, self::Refunded => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Refunded], true);
    }
}
