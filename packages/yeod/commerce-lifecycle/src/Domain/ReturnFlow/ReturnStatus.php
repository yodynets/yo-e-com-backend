<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\ReturnFlow;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum ReturnStatus: string implements TransitionableStatus
{
    case Requested         = 'requested';
    case Approved          = 'approved';
    case Rejected          = 'rejected';
    case LabelIssued       = 'label_issued';
    case InTransit         = 'in_transit';
    case Received          = 'received';
    case Inspecting        = 'inspecting';
    case Accepted          = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
    case Refunded          = 'refunded';
    case Replaced          = 'replaced';
    case Closed            = 'closed';

    /**
     * @param  ReturnStatus  $target
     *
     * @return bool
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Requested => in_array($target, [self::Approved, self::Rejected], true),
            self::Approved => in_array($target, [self::LabelIssued, self::Rejected], true),
            self::LabelIssued => $target === self::InTransit,
            self::InTransit => $target === self::Received,
            self::Received => $target === self::Inspecting,
            self::Inspecting => in_array($target, [self::Accepted, self::PartiallyAccepted, self::Rejected], true),
            self::Accepted, self::PartiallyAccepted => in_array(
                $target,
                [self::Refunded, self::Replaced, self::Closed],
                true
            ),
            self::Rejected, self::Refunded, self::Replaced, self::Closed => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Refunded, self::Replaced, self::Closed], true);
    }
}
