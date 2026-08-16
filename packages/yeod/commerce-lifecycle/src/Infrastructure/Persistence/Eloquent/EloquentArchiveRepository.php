<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Support\Carbon;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

/**
 * Eloquent adapter for deep archive snapshots.
 */
final class EloquentArchiveRepository implements ArchiveRepository
{
    /**
     * Persist or replace a deep snapshot without deleting the source record.
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null
    ): void {
        ArchiveRecordModel::query()->updateOrCreate(
            ['archivable_type' => $type, 'archivable_id' => $id],
            [
                'reason'      => $reason,
                'archived_by' => $archivedBy,
                'snapshot'    => $snapshot,
                'archived_at' => Carbon::now(),
                'restored_at' => null,
            ],
        );
    }

    /**
     * Mark the latest snapshot of a record as restored.
     */
    public function restore(string $type, string $id): void
    {
        ArchiveRecordModel::query()
            ->where('archivable_type', $type)
            ->where('archivable_id', $id)
            ->update(['restored_at' => Carbon::now()]);
    }
}
