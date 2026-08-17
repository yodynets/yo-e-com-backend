<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Application\Fulfillment\FulfillmentSnapshot;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;

/**
 * Verifies that the transport shape of a fulfillment is produced by the
 * application-level snapshot mapper instead of the domain aggregate.
 */
final class FulfillmentSnapshotTest extends TestCase
{
    public function test_from_produces_the_transport_shape(): void
    {
        $fulfillment = Fulfillment::create(
            'ful-1',
            'ord-1',
            [
                new FulfillmentLine('line-1', 'sku-1', 2),
                new FulfillmentLine('line-2', 'sku-2', 3),
            ],
            metadata: ['channel' => 'web'],
        );

        $fulfillment->fulfillLine('line-1', 1);
        self::assertSame(FulfillmentStatus::PartiallyFulfilled, $fulfillment->status());

        $snapshot = FulfillmentSnapshot::from($fulfillment);

        self::assertSame('ful-1', $snapshot['id']);
        self::assertSame('ord-1', $snapshot['order_id']);
        self::assertSame('partially_fulfilled', $snapshot['status']);
        self::assertSame(['channel' => 'web'], $snapshot['metadata']);
        self::assertSame('line-1', $snapshot['lines'][0]['id']);
        self::assertSame('sku-1', $snapshot['lines'][0]['sku']);
        self::assertSame(2, $snapshot['lines'][0]['ordered_quantity']);
        self::assertSame(1, $snapshot['lines'][0]['fulfilled_quantity']);
    }
}
