<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;
use Yeod\CommerceLifecycle\Tests\Doubles\FakeArchiveRepository;

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
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository);

        $service->archive(
            'order',
            'ord-1',
            ['total' => 100],
            'customer request',
            'user-1',
            'analytics-db',
        );

        self::assertSame([
            [
                'type' => 'order',
                'id' => 'ord-1',
                'snapshot' => ['total' => 100],
                'reason' => 'customer request',
                'archivedBy' => 'user-1',
                'storageLocation' => 'analytics-db',
            ],
        ], $repository->archived);
    }

    public function test_restore_delegates_to_repository(): void
    {
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository);

        $service->restore('order', 'ord-1');

        self::assertSame([['type' => 'order', 'id' => 'ord-1']], $repository->restored);
    }

    public function test_find_snapshot_delegates_to_repository(): void
    {
        $repository = new FakeArchiveRepository;
        $repository->snapshots['order:ord-1'] = ['total' => 100];
        $service = new ArchiveService($repository);

        self::assertSame(['total' => 100], $service->findSnapshot('order', 'ord-1'));
    }

    public function test_is_archived_delegates_to_repository(): void
    {
        $repository = new FakeArchiveRepository;
        $repository->archivedKeys['order:ord-1'] = true;
        $service = new ArchiveService($repository);

        self::assertTrue($service->isArchived('order', 'ord-1'));
        self::assertFalse($service->isArchived('order', 'ord-2'));
    }
}
