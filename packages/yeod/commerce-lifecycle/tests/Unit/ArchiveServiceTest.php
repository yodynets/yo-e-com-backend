<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;

/**
 * Verifies that ArchiveService delegates to the archive repository port.
 *
 * The Eloquent adapter itself is a thin persistence layer and is exercised in
 * the host application; this unit test locks in the application-level contract.
 */
final class ArchiveServiceTest extends TestCase
{
    public function test_archive_delegates_to_repository(): void
    {
        $repository = new FakeArchiveRepository();
        $service = new ArchiveService($repository);

        $service->archive('order', 'ord-1', ['total' => 100], 'customer request', 'user-1');

        self::assertSame([['type' => 'order', 'id' => 'ord-1', 'snapshot' => ['total' => 100], 'reason' => 'customer request', 'archivedBy' => 'user-1']], $repository->archived);
    }

    public function test_restore_delegates_to_repository(): void
    {
        $repository = new FakeArchiveRepository();
        $service = new ArchiveService($repository);

        $service->restore('order', 'ord-1');

        self::assertSame([['type' => 'order', 'id' => 'ord-1']], $repository->restored);
    }
}

/**
 * @internal Test double implementing the persistence port.
 */
final class FakeArchiveRepository implements ArchiveRepository
{
    /** @var list<array{type: string, id: string, snapshot: array<string, mixed>, reason: ?string, archivedBy: ?string}> */
    public array $archived = [];

    /** @var list<array{type: string, id: string}> */
    public array $restored = [];

    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null
    ): void {
        $this->archived[] = compact('type', 'id', 'snapshot', 'reason', 'archivedBy');
    }

    public function restore(string $type, string $id): void
    {
        $this->restored[] = compact('type', 'id');
    }
}