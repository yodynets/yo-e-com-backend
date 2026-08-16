<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Shipment;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum ShipmentStatus: string implements TransitionableStatus
{
    case LabelCreated     = 'label_created';
    case AwaitingPickup   = 'awaiting_pickup';
    case InTransit        = 'in_transit';
    case OutForDelivery   = 'out_for_delivery';
    case Delivered        = 'delivered';
    case DeliveryFailed   = 'delivery_failed';
    case ReturnedToSender = 'returned_to_sender';
    case Cancelled        = 'cancelled';

    /**
     * @param  ShipmentStatus  $target
     *
     * @return bool
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::LabelCreated => in_array($target, [self::AwaitingPickup, self::Cancelled], true),
            self::AwaitingPickup => in_array($target, [self::InTransit, self::Cancelled], true),
            self::InTransit => in_array(
                $target,
                [self::OutForDelivery, self::Delivered, self::DeliveryFailed, self::ReturnedToSender],
                true
            ),
            self::OutForDelivery => in_array(
                $target,
                [self::Delivered, self::DeliveryFailed, self::ReturnedToSender],
                true
            ),
            self::DeliveryFailed => in_array($target, [self::InTransit, self::ReturnedToSender], true),
            self::Delivered, self::ReturnedToSender, self::Cancelled => false,
        };
    }

    /** Determine whether the status is terminal and cannot transition further. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::ReturnedToSender, self::Cancelled], true);
    }
}
