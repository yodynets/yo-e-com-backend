<?php

declare(strict_types = 1);

namespace Yeod\CommerceLifecycle\Application\Archive;

use InvalidArgumentException;
use JsonException;
use Yeod\CommerceLifecycle\Application\Authorizer;
use Yeod\CommerceLifecycle\Domain\Archive\ArchiveRepository;
use Yeod\CommerceLifecycle\Exceptions\NotAuthorizedException;

/**
 * Application service for deep archival.
 *
 * Archiving removes an object from an application's active read model only
 * when the host application chooses to do so. This service preserves a full
 * snapshot for analytics and audit; it never deletes source data by itself.
 *
 * This class stays framework-free: size/reason limits are constructor options
 * (the service provider injects the published config values), authorization is
 * an optional domain port, and failures raise the package exception hierarchy.
 */
final readonly class ArchiveService
{
    public function __construct(
        private ArchiveRepository $repository,
        private int $maxSnapshotSizeInKb = 512,
        private int $maxReasonLength = 1000,
        private ?Authorizer $authorizer = null,
    ) {}

    /**
     * Deep-archive a record without deleting the source data.
     *
     * @param  array<string, mixed>  $snapshot
     *
     * @throws InvalidArgumentException
     * @throws NotAuthorizedException
     */
    public function archive(
        string $type,
        string $id,
        array $snapshot,
        ?string $reason = null,
        ?string $archivedBy = null,
        ?string $storageLocation = null,
    ): void {
        if ($this->authorizer !== null && ! $this->authorizer->can('archive', $type)) {
            throw new NotAuthorizedException(sprintf('Not authorized to archive %s records.', $type));
        }

        if (empty($type) || strlen($type) > 255) {
            throw new InvalidArgumentException('Archive type must be between 1 and 255 characters.');
        }

        if (empty($id) || strlen($id) > 255) {
            throw new InvalidArgumentException('Archive id must be between 1 and 255 characters.');
        }

        if ($reason !== null && strlen($reason) > $this->maxReasonLength) {
            throw new InvalidArgumentException(
                sprintf('Archive reason exceeds the maximum length of %d characters.', $this->maxReasonLength)
            );
        }

        if (empty($snapshot)) {
            throw new InvalidArgumentException('Archive snapshot cannot be empty.');
        }

        try {
            $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Snapshot contains non-serializable data.', 0, $e);
        }

        $maxSizeInBytes = $this->maxSnapshotSizeInKb * 1024;
        if (strlen($encoded) > $maxSizeInBytes) {
            throw new InvalidArgumentException(
                sprintf('Snapshot exceeds the allowed size of %d kilobytes.', $this->maxSnapshotSizeInKb)
            );
        }

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