<?php

declare(strict_types=1);

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
     * @param  string|null  $storageLocation  Marker where the snapshot is physically
     *                                        stored (e.g. an analytics database name).
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null,
        ?string $storageLocation = null
    ): void;

    /**
     * Mark the latest snapshot as restored.
     */
    public function restore(string $type, string $id): void;

    /**
     * Return the latest active snapshot for a record, or null when it is not
     * currently archived.
     *
     * @return array<string, mixed>|null
     */
    public function findSnapshot(string $type, string $id): ?array;

    /**
     * Determine whether a record currently has an archived snapshot.
     */
    public function isArchived(string $type, string $id): bool;
}
