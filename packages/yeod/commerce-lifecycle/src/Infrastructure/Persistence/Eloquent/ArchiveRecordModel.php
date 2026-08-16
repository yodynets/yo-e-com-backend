<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 * Deep archive storage model. The JSON snapshot is retained for reporting.
 */
final class ArchiveRecordModel extends Model
{
    public    $timestamps = false;
    protected $table      = 'commerce_archives';
    protected $fillable   = [
        'archivable_type',
        'archivable_id',
        'reason',
        'archived_by',
        'storage_location',
        'snapshot',
        'archived_at',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot'    => 'array',
            'archived_at' => 'immutable_datetime',
            'restored_at' => 'immutable_datetime',
        ];
    }
}
