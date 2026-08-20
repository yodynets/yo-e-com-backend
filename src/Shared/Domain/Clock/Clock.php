<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Clock;

use DateTimeImmutable;

/**
 * Port that gives the Domain access to the current time.
 *
 * Never call `now()` or `new DateTimeImmutable()` inside the Domain: injecting a
 * clock is what makes time dependent rules testable.
 */
interface Clock
{
    /**
     * Current moment in UTC.
     */
    public function now(): DateTimeImmutable;
}
