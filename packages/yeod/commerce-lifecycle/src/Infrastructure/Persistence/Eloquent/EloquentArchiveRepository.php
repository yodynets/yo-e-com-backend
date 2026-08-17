<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Carbon;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

/**
 * Eloquent adapter for deep archive snapshots.
 */
final class EloquentArchiveRepository implements ArchiveRepository
{
    /**
     * Append a new versioned deep snapshot without deleting the source record.
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null,
        ?string $storageLocation = null
    ): void {
        ArchiveRecordModel::query()->create([
            'archivable_type' => $type,
            'archivable_id' => $id,
            'snapshot_version' => $this->nextVersion($type, $id),
            'reason' => $reason,
            'archived_by' => $archivedBy,
            'storage_location' => $storageLocation,
            'snapshot' => $snapshot,
            'archived_at' => Carbon::now(),
            'restored_at' => null,
        ]);
    }

    private function nextVersion(string $type, string $id): int
    {
        return (int) ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->max('snapshot_version') + 1;
    }

    /**
     * Mark the latest snapshot of a record as restored.
     */
    public function restore(string $type, string $id): void
    {
        $latestVersion = ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->max('snapshot_version');

        if ($latestVersion === null) {
            return;
        }

        ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->where('snapshot_version', $latestVersion)
            ->update(['restored_at' => Carbon::now()]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findSnapshot(string $type, string $id): ?array
    {
        return ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->whereNull('restored_at')
            ->orderByDesc('snapshot_version')
            ->value('snapshot');
    }

    public function isArchived(string $type, string $id): bool
    {
        return ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->whereNull('restored_at')
            ->exists();
    }
}
