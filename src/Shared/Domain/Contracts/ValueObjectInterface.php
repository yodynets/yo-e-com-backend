<?php

/**
 * @package: mdb-backend
 * @author: Yevhen Odynets
 * @date: 2026-02-27
 * @time: 12:48
 */

declare(strict_types = 1);

namespace Yeod\Shared\Domain\Contracts;

use Stringable;
use InvalidArgumentException;

/**
 * Contract shared by every value object of the Shared Kernel.
 *
 * Value objects are immutable, self validating and compared by value, never by
 * identity. Implementations must be declared `final` and `readonly`.
 *
 * @template T
 */
interface ValueObjectInterface extends Stringable
{
    /**
     * Create from non-null value.
     *
     * @param  T  $value
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public static function from(mixed $value): static;

    /**
     * Create from nullable value.
     *
     * @param  T|null  $value
     *
     * @return static|null
     */
    public static function fromNullable(mixed $value): ?static;

    /**
     * Get the underlying value.
     *
     * @return T
     */
    public function value(): mixed;

    /**
     * Determine whether the given value object is equal to this one.
     *
     * @param  self  $other  Value object to compare against.
     */
    public function equals(self $other): bool;

    /**
     * Return the primitive representation used for persistence and transport.
     */
//    public function toPrimitive(): string|int|float|bool;

    /**
     * String representation.
     */
    public function __toString(): string;
}
