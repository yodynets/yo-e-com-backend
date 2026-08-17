<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Application\Fulfillment;

use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;

/**
 * Maps a fulfillment aggregate to its serializable transport shape.
 *
 * Keeps the persistence/transport representation out of the domain layer: the
 * aggregate exposes only typed getters, and this application-level snapshot
 * produces the snake_case array used for deep archival and reporting.
 */
final class FulfillmentSnapshot
{
    /**
     * @return array{
     *   id: string,
     *   order_id: string,
     *   status: string,
     *   metadata: array<string, mixed>,
     *   created_at: string,
     *   lines: list<array{id: string, sku: string, ordered_quantity: int, fulfilled_quantity: int}>
     * }
     */
    public static function from(Fulfillment $fulfillment): array
    {
        return [
            'id' => $fulfillment->id(),
            'order_id' => $fulfillment->orderId(),
            'status' => $fulfillment->status()->value,
            'metadata' => $fulfillment->metadata(),
            'created_at' => $fulfillment->createdAt()->format(DATE_ATOM),
            'lines' => array_map(
                static fn (FulfillmentLine $line): array => $line->toArray(),
                $fulfillment->lines(),
            ),
        ];
    }
}
