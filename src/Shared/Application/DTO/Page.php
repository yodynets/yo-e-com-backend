<?php

declare(strict_types=1);

namespace Yeod\Shared\Application\DTO;

/**
 * A single page of read model items.
 *
 * @template TItem of Arrayable
 */
final readonly class Page implements Arrayable
{
    /**
     * @param  list<TItem>  $items  Items belonging to the requested page.
     * @param  int  $total  Total number of matching items across all pages.
     * @param  Pagination  $pagination  Page request that produced this page.
     */
    public function __construct(
        public array $items,
        public int $total,
        public Pagination $pagination,
    ) {}

    /**
     * Total number of pages available for the current page size.
     */
    public function lastPage(): int
    {
        return (int) max(1, ceil($this->total / $this->pagination->perPage));
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (Arrayable $item): array => $item->toArray(), $this->items),
            'meta' => [
                'current_page' => $this->pagination->page,
                'per_page' => $this->pagination->perPage,
                'last_page' => $this->lastPage(),
                'total' => $this->total,
            ],
        ];
    }
}
