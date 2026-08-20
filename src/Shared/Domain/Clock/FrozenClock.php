<?php

declare(strict_types=1);

namespace Yeod\Shared\Domain\Clock;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Test double that always reports the same moment.
 *
 * It ships with the Domain (not with the tests) so every module can use it
 * without duplicating a fixture.
 */
final class FrozenClock implements Clock
{
    /**
     * @param  DateTimeImmutable  $now  Moment to report on every call.
     */
    public function __construct(private DateTimeImmutable $now) {}

    /**
     * Create a frozen clock from an ISO 8601 string interpreted as UTC.
     *
     * @param  string  $iso8601  Moment, for example `2026-01-01T00:00:00`.
     */
    public static function at(string $iso8601): self
    {
        return new self(new DateTimeImmutable($iso8601, new DateTimeZone('UTC')));
    }

    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    /**
     * Move the frozen moment forward, for example `+1 day`.
     *
     * @param  string  $modifier  Relative date/time modifier accepted by PHP.
     */
    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
