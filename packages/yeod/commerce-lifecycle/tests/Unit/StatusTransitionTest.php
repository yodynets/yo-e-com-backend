<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Domain\Catalog\ProductAvailabilityStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;
use Yeod\CommerceLifecycle\Domain\Payment\PaymentStatus;
use Yeod\CommerceLifecycle\Domain\ReturnFlow\ReturnStatus;
use Yeod\CommerceLifecycle\Domain\Shipment\ShipmentStatus;

final class StatusTransitionTest extends TestCase
{
    public function test_status_graphs_keep_separate_business_axes(): void
    {
        self::assertTrue(OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::AwaitingFulfillment));
        self::assertFalse(OrderStatus::AwaitingPayment->canTransitionTo(OrderStatus::Completed));
        self::assertTrue(PaymentStatus::Captured->canTransitionTo(PaymentStatus::PartiallyRefunded));
        self::assertTrue(FulfillmentStatus::Unfulfilled->canTransitionTo(FulfillmentStatus::PartiallyFulfilled));
        self::assertTrue(ShipmentStatus::InTransit->canTransitionTo(ShipmentStatus::Delivered));
        self::assertTrue(ReturnStatus::Inspecting->canTransitionTo(ReturnStatus::PartiallyAccepted));
        self::assertTrue(ProductAvailabilityStatus::Available->canTransitionTo(ProductAvailabilityStatus::Archived));
    }
}
