<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Archive;

/**
 * Operational visibility state independent of business status.
 */
enum ArchiveState: string
{
    case Active   = 'active';
    case Archived = 'archived';
}
