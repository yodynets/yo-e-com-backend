<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Payment;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum PaymentStatus: string implements TransitionableStatus
{
    case Pending           = 'pending';
    case Authorized        = 'authorized';
    case Captured          = 'captured';
    case Failed            = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded          = 'refunded';
    case Voided            = 'voided';

    /**
     * @param  PaymentStatus  $target
     *
     * @return bool
     */
    public function canTransitionTo(TransitionableStatus $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Authorized, self::Captured, self::Failed, self::Voided], true),
            self::Authorized => in_array($target, [self::Captured, self::Voided, self::Failed], true),
            self::Captured => in_array($target, [self::PartiallyRefunded, self::Refunded], true),
            self::PartiallyRefunded => $target === self::Refunded,
            self::Failed, self::Refunded, self::Voided => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Failed, self::Refunded, self::Voided], true);
    }
}
