<?php

declare(strict_types=1);

namespace Yeod\Shared\Infrastructure\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Yeod\Shared\Domain\Clock\Clock;

/**
 * Production clock backed by the system time, always in UTC.
 */
final readonly class SystemClock implements Clock
{
    /**
     * {@inheritDoc}
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
