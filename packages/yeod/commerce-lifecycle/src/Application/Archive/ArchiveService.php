<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Application\Archive;

use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

/**
 * Application service for deep archival.
 *
 * Archiving removes an object from an application's active read model only
 * when the host application chooses to do so. This service preserves a full
 * snapshot for analytics and audit; it never deletes source data by itself.
 */
final readonly class ArchiveService
{
    public function __construct(private ArchiveRepository $repository) {}

    /**
     * Deep-archive a record without deleting the source data.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null,
        ?string $storageLocation = null,
    ): void {
        $this->repository->archive($type, $id, $snapshot, $reason, $archivedBy, $storageLocation);
    }

    /**
     * Mark the latest archive snapshot of a record as restored.
     */
    public function restore(string $type, string $id): void
    {
        $this->repository->restore($type, $id);
    }

    /**
     * Return the stored snapshot for a record, or null when it is not archived.
     *
     * @return array<string, mixed>|null
     */
    public function findSnapshot(string $type, string $id): ?array
    {
        return $this->repository->findSnapshot($type, $id);
    }

    /**
     * Determine whether a record currently has an archived snapshot.
     */
    public function isArchived(string $type, string $id): bool
    {
        return $this->repository->isArchived($type, $id);
    }
}
