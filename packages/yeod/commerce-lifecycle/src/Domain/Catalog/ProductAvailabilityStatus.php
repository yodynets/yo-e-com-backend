<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Domain\Catalog;

use Yeod\CommerceLifecycle\Domain\Shared\TransitionableStatus;

enum ProductAvailabilityStatus: string implements TransitionableStatus
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Available = 'available';
    case TemporarilyUnavailable = 'temporarily_unavailable';
    case Discontinued = 'discontinued';
    case Archived = 'archived';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Scheduled, self::Available, self::Archived], true),
            self::Scheduled => in_array($target, [self::Available, self::Draft, self::Archived], true),
            self::Available => in_array(
                $target,
                [self::TemporarilyUnavailable, self::Discontinued, self::Archived],
                true
            ),
            self::TemporarilyUnavailable => in_array(
                $target,
                [self::Available, self::Discontinued, self::Archived],
                true
            ),
            self::Discontinued => $target === self::Archived,
            self::Archived => $target === self::Draft,
        };
    }

    /**
     * Determine whether the status is terminal and cannot transition further.
     *
     * No product availability status is truly terminal: `Discontinued` can still
     * transition to `Archived`, and `Archived` back to `Draft`. So this always
     * returns false, matching the graph.
     */
    public function isFinal(): bool
    {
        return false;
    }

    /** Determine whether the product may be sold right now. */
    public function isSellable(): bool
    {
        return $this === self::Available;
    }
}
