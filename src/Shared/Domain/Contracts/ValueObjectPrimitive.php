<?php

declare(strict_types = 1);

namespace Yeod\Shared\Domain\Contracts;

use Stringable;

/**
 * Contract shared by every value object of the Shared Kernel.
 *
 * Value objects are immutable, self validating and compared by value, never by
 * identity. Implementations must be declared `final` and `readonly`.
 */
interface ValueObjectPrimitive extends Stringable
{
    /**
     * Determine whether the given value object is equal to this one.
     *
     * @param  self  $other  Value object to compare against.
     */
    public function equals(self $other): bool;

    /**
     * Return the primitive representation used for persistence and transport.
     */
    public function toPrimitive(): string|int|float|bool;
}
