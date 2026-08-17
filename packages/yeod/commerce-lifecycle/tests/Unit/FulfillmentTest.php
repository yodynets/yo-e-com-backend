<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Exceptions\InvalidArgumentException;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

final class FulfillmentTest extends TestCase
{
    public function test_partial_and_full_fulfillment_are_derived_from_lines(): void
    {
        $fulfillment = Fulfillment::create('ful-1', 'ord-1', [
            new FulfillmentLine('line-1', 'sku-1', 2),
            new FulfillmentLine('line-2', 'sku-2', 1),
        ]);

        $fulfillment->fulfillLine('line-1', 1);
        self::assertSame(FulfillmentStatus::PartiallyFulfilled, $fulfillment->status());
        $fulfillment->fulfillLine('line-1', 1);
        $fulfillment->fulfillLine('line-2', 1);
        self::assertSame(FulfillmentStatus::Fulfilled, $fulfillment->status());
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $fulfillment = Fulfillment::create('ful-1', 'ord-1', [new FulfillmentLine('line-1', 'sku-1', 1)]);
        $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
        $fulfillment->changeStatus(FulfillmentStatus::Fulfilled);

        $this->expectException(InvalidTransitionException::class);
        $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
    }

    public function test_fulfill_line_over_quantity_does_not_mutate_status_or_events(): void
    {
        $fulfillment = Fulfillment::create('ful-1', 'ord-1', [new FulfillmentLine('line-1', 'sku-1', 2)]);
        $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
        $fulfillment->releaseEvents(); // discard the previous transition event

        try {
            $fulfillment->fulfillLine('line-1', 5);
            self::fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException) {
            // expected
        }

        self::assertSame([], $fulfillment->releaseEvents(), 'A failed fulfill must not queue any event.');
        self::assertSame(FulfillmentStatus::Unfulfilled, $fulfillment->status(), 'Status must not change.');
        self::assertSame(0, $fulfillment->lines()[0]->fulfilledQuantity(), 'Line quantity must not change.');
    }
}
