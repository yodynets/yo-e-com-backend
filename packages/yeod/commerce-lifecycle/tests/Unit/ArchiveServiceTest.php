<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Application\Archive\ArchiveService;
use Yeod\CommerceLifecycle\Application\DenyAllAuthorizer;
use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;
use Yeod\CommerceLifecycle\Exceptions\NotAuthorizedException;
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

    public function test_reason_length_is_measured_in_characters_not_bytes(): void
    {
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository, maxReasonLength: 3);

        // 'ї' is 2 bytes in UTF-8 but 1 character: 4 characters exceed the limit of 3.
        $this->expectException(InvalidArgumentException::class);
        $service->archive('order', 'ord-1', ['total' => 1], reason: 'їїїї');
    }

    public function test_reason_at_the_limit_in_characters_is_allowed(): void
    {
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository, maxReasonLength: 3);

        $service->archive('order', 'ord-1', ['total' => 1], reason: 'їїї');

        self::assertCount(1, $repository->archived);
    }

    public function test_archive_rejects_empty_type(): void
    {
        $service = new ArchiveService(new FakeArchiveRepository);

        $this->expectException(InvalidArgumentException::class);

        $service->archive('', 'ord-1', ['total' => 1]);
    }

    public function test_archive_rejects_empty_id(): void
    {
        $service = new ArchiveService(new FakeArchiveRepository);

        $this->expectException(InvalidArgumentException::class);

        $service->archive('order', '', ['total' => 1]);
    }

    public function test_archive_rejects_empty_snapshot(): void
    {
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository);

        $this->expectException(InvalidArgumentException::class);

        $service->archive('order', 'ord-1', []);
    }

    public function test_archive_rejects_oversized_snapshot(): void
    {
        $repository = new FakeArchiveRepository;
        $service = new ArchiveService($repository, maxSnapshotSizeInKb: 1);

        $this->expectException(InvalidArgumentException::class);

        $service->archive('order', 'ord-1', ['blob' => str_repeat('x', 2048)]);
    }

    public function test_operations_are_denied_by_the_fail_closed_authorizer(): void
    {
        $service = new ArchiveService(new FakeArchiveRepository, authorizer: new DenyAllAuthorizer);

        self::expectException(NotAuthorizedException::class);
        $service->archive('order', 'ord-1', ['total' => 1]);
    }

    public function test_restore_is_denied_by_the_fail_closed_authorizer(): void
    {
        $service = new ArchiveService(new FakeArchiveRepository, authorizer: new DenyAllAuthorizer);

        $this->expectException(NotAuthorizedException::class);
        $service->restore('order', 'ord-1');
    }
}
