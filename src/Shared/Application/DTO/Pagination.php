<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\DTO;

use InvalidArgumentException;

/**
 * Page request used by list queries across every module.
 */
final readonly class Pagination
{
    /**
     * Largest page size the read side will ever return.
     */
    public const int MAX_PER_PAGE = 200;

    /**
     * @param  int  $page  One based page number.
     * @param  int  $perPage  Number of items per page.
     *
     * @throws InvalidArgumentException When the page or page size is out of range.
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
    ) {
        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than or equal to 1.');
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new InvalidArgumentException(sprintf('Per page must be between 1 and %d.', self::MAX_PER_PAGE));
        }
    }

    /**
     * Zero based offset for SQL `OFFSET` clauses.
     */
    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
