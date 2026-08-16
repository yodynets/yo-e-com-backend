<?php

declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Shared\TransitionException;

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

        $this->expectException(TransitionException::class);
        $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
    }
}
