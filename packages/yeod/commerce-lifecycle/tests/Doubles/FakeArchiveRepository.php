<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Doubles;

use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

/**
 * @internal Test double implementing the persistence port.
 */
final class FakeArchiveRepository implements ArchiveRepository
{
    /**
     * @var list<array{type: string, id: string, snapshot: array<string, mixed>, reason: ?string, archivedBy: ?string, storageLocation: ?string}>
     */
    public array $archived = [];

    /** @var list<array{type: string, id: string}> */
    public array $restored = [];

    /** @var array<string, array<string, mixed>> */
    public array $snapshots = [];

    /** @var array<string, bool> */
    public array $archivedKeys = [];

    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null,
        ?string $storageLocation = null
    ): void {
        $this->archived[] = compact('type', 'id', 'snapshot', 'reason', 'archivedBy', 'storageLocation');
        $this->snapshots["$type:$id"] = $snapshot;
        $this->archivedKeys["$type:$id"] = true;
    }

    public function restore(string $type, string $id): void
    {
        $this->restored[] = compact('type', 'id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findSnapshot(string $type, string $id): ?array
    {
        return $this->snapshots["$type:$id"] ?? null;
    }

    public function isArchived(string $type, string $id): bool
    {
        return $this->archivedKeys["$type:$id"] ?? false;
    }
}
