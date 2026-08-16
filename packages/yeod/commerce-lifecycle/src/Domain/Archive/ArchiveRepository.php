<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Domain\Archive;

/**
 * Persistence port for immutable operational archive snapshots.
 */
interface ArchiveRepository
{
    /**
     * Persist or replace a deep snapshot without deleting the source record.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null
    ): void;

    /**
     * Mark the latest snapshot as restored.
     */
    public function restore(string $type, string $id): void;
}
