<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Domain\Catalog\ProductAvailabilityStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;
use Yeod\CommerceLifecycle\Domain\Payment\PaymentStatus;
use Yeod\CommerceLifecycle\Domain\ReturnFlow\ReturnStatus;
use Yeod\CommerceLifecycle\Domain\Shipment\ShipmentStatus;

/**
 * Verifies the transition graph of every status axis. Each case asserts that a
 * allowed transition is accepted, a disallowed one is rejected, and terminal
 * states are correctly reported via the shared `isFinal()` contract.
 */
final class StatusGraphTest extends TestCase
{
    #[DataProvider('orderTransitionsProvider')]
    public function test_order_status_graph(OrderStatus $from, OrderStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{OrderStatus, OrderStatus, bool}>
     */
    public static function orderTransitionsProvider(): iterable
    {
        yield 'pending -> awaiting_payment' => [OrderStatus::Pending, OrderStatus::AwaitingPayment, true];
        yield 'pending -> cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true];
        yield 'pending -> completed' => [OrderStatus::Pending, OrderStatus::Completed, false];
        yield 'payment_failed -> awaiting_payment' => [OrderStatus::PaymentFailed, OrderStatus::AwaitingPayment, true];
        yield 'awaiting_payment -> awaiting_fulfillment' => [OrderStatus::AwaitingPayment, OrderStatus::AwaitingFulfillment, true];
        yield 'awaiting_payment -> payment_failed' => [OrderStatus::AwaitingPayment, OrderStatus::PaymentFailed, true];
        yield 'awaiting_payment -> shipped' => [OrderStatus::AwaitingPayment, OrderStatus::Shipped, false];
        yield 'awaiting_fulfillment -> awaiting_pickup' => [OrderStatus::AwaitingFulfillment, OrderStatus::AwaitingPickup, true];
        yield 'awaiting_fulfillment -> shipped' => [OrderStatus::AwaitingFulfillment, OrderStatus::Shipped, true];
        yield 'awaiting_pickup -> shipped' => [OrderStatus::AwaitingPickup, OrderStatus::Shipped, true];
        yield 'shipped -> completed' => [OrderStatus::Shipped, OrderStatus::Completed, true];
        yield 'shipped -> refunded' => [OrderStatus::Shipped, OrderStatus::Refunded, true];
        yield 'completed -> refunded' => [OrderStatus::Completed, OrderStatus::Refunded, true];
        yield 'cancelled is final, no outgoing' => [OrderStatus::Cancelled, OrderStatus::AwaitingPayment, false];
        yield 'refunded is final, no outgoing' => [OrderStatus::Refunded, OrderStatus::Completed, false];
    }

    #[DataProvider('paymentStatusTransitionsProvider')]
    public function test_payment_status_graph(PaymentStatus $from, PaymentStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{PaymentStatus, PaymentStatus, bool}>
     */
    public static function paymentStatusTransitionsProvider(): iterable
    {
        yield 'pending -> authorized' => [PaymentStatus::Pending, PaymentStatus::Authorized, true];
        yield 'pending -> captured' => [PaymentStatus::Pending, PaymentStatus::Captured, true];
        yield 'pending -> failed' => [PaymentStatus::Pending, PaymentStatus::Failed, true];
        yield 'authorized -> captured' => [PaymentStatus::Authorized, PaymentStatus::Captured, true];
        yield 'authorized -> voided' => [PaymentStatus::Authorized, PaymentStatus::Voided, true];
        yield 'captured -> partially_refunded' => [PaymentStatus::Captured, PaymentStatus::PartiallyRefunded, true];
        yield 'captured -> refunded' => [PaymentStatus::Captured, PaymentStatus::Refunded, true];
        yield 'partially_refunded -> refunded' => [PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded, true];
        yield 'captured -> authorized' => [PaymentStatus::Captured, PaymentStatus::Authorized, false];
        yield 'failed is final' => [PaymentStatus::Failed, PaymentStatus::Pending, false];
        yield 'voided is final' => [PaymentStatus::Voided, PaymentStatus::Pending, false];
    }

    #[DataProvider('fulfillmentStatusTransitionsProvider')]
    public function test_fulfillment_status_graph(FulfillmentStatus $from, FulfillmentStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{FulfillmentStatus, FulfillmentStatus, bool}>
     */
    public static function fulfillmentStatusTransitionsProvider(): iterable
    {
        yield 'scheduled -> unfulfilled' => [FulfillmentStatus::Scheduled, FulfillmentStatus::Unfulfilled, true];
        yield 'scheduled -> on_hold' => [FulfillmentStatus::Scheduled, FulfillmentStatus::OnHold, true];
        yield 'scheduled -> cancelled' => [FulfillmentStatus::Scheduled, FulfillmentStatus::Cancelled, true];
        yield 'unfulfilled -> partially_fulfilled' => [FulfillmentStatus::Unfulfilled, FulfillmentStatus::PartiallyFulfilled, true];
        yield 'partially_fulfilled -> fulfilled' => [FulfillmentStatus::PartiallyFulfilled, FulfillmentStatus::Fulfilled, true];
        yield 'on_hold -> unfulfilled' => [FulfillmentStatus::OnHold, FulfillmentStatus::Unfulfilled, true];
        yield 'on_hold -> cancelled' => [FulfillmentStatus::OnHold, FulfillmentStatus::Cancelled, true];
        yield 'fulfilled cannot reopen' => [FulfillmentStatus::Fulfilled, FulfillmentStatus::Unfulfilled, false];
        yield 'cancelled is final' => [FulfillmentStatus::Cancelled, FulfillmentStatus::Scheduled, false];
    }

    #[DataProvider('shipmentStatusTransitionsProvider')]
    public function test_shipment_status_graph(ShipmentStatus $from, ShipmentStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{ShipmentStatus, ShipmentStatus, bool}>
     */
    public static function shipmentStatusTransitionsProvider(): iterable
    {
        yield 'label_created -> awaiting_pickup' => [ShipmentStatus::LabelCreated, ShipmentStatus::AwaitingPickup, true];
        yield 'label_created -> cancelled' => [ShipmentStatus::LabelCreated, ShipmentStatus::Cancelled, true];
        yield 'awaiting_pickup -> in_transit' => [ShipmentStatus::AwaitingPickup, ShipmentStatus::InTransit, true];
        yield 'in_transit -> out_for_delivery' => [ShipmentStatus::InTransit, ShipmentStatus::OutForDelivery, true];
        yield 'in_transit -> delivered' => [ShipmentStatus::InTransit, ShipmentStatus::Delivered, true];
        yield 'in_transit -> delivery_failed' => [ShipmentStatus::InTransit, ShipmentStatus::DeliveryFailed, true];
        yield 'in_transit -> returned_to_sender' => [ShipmentStatus::InTransit, ShipmentStatus::ReturnedToSender, true];
        yield 'out_for_delivery -> delivered' => [ShipmentStatus::OutForDelivery, ShipmentStatus::Delivered, true];
        yield 'out_for_delivery -> delivery_failed' => [ShipmentStatus::OutForDelivery, ShipmentStatus::DeliveryFailed, true];
        yield 'delivery_failed -> in_transit' => [ShipmentStatus::DeliveryFailed, ShipmentStatus::InTransit, true];
        yield 'delivery_failed -> returned_to_sender' => [ShipmentStatus::DeliveryFailed, ShipmentStatus::ReturnedToSender, true];
        yield 'delivered is final' => [ShipmentStatus::Delivered, ShipmentStatus::InTransit, false];
        yield 'cancelled is final' => [ShipmentStatus::Cancelled, ShipmentStatus::LabelCreated, false];
    }

    #[DataProvider('returnStatusTransitionsProvider')]
    public function test_return_status_graph(ReturnStatus $from, ReturnStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{ReturnStatus, ReturnStatus, bool}>
     */
    public static function returnStatusTransitionsProvider(): iterable
    {
        yield 'requested -> approved' => [ReturnStatus::Requested, ReturnStatus::Approved, true];
        yield 'requested -> rejected' => [ReturnStatus::Requested, ReturnStatus::Rejected, true];
        yield 'approved -> label_issued' => [ReturnStatus::Approved, ReturnStatus::LabelIssued, true];
        yield 'approved -> rejected' => [ReturnStatus::Approved, ReturnStatus::Rejected, true];
        yield 'label_issued -> in_transit' => [ReturnStatus::LabelIssued, ReturnStatus::InTransit, true];
        yield 'in_transit -> received' => [ReturnStatus::InTransit, ReturnStatus::Received, true];
        yield 'received -> inspecting' => [ReturnStatus::Received, ReturnStatus::Inspecting, true];
        yield 'inspecting -> accepted' => [ReturnStatus::Inspecting, ReturnStatus::Accepted, true];
        yield 'inspecting -> partially_accepted' => [ReturnStatus::Inspecting, ReturnStatus::PartiallyAccepted, true];
        yield 'inspecting -> rejected' => [ReturnStatus::Inspecting, ReturnStatus::Rejected, true];
        yield 'accepted -> refunded' => [ReturnStatus::Accepted, ReturnStatus::Refunded, true];
        yield 'accepted -> replaced' => [ReturnStatus::Accepted, ReturnStatus::Replaced, true];
        yield 'accepted -> closed' => [ReturnStatus::Accepted, ReturnStatus::Closed, true];
        yield 'partially_accepted -> refunded' => [ReturnStatus::PartiallyAccepted, ReturnStatus::Refunded, true];
        yield 'rejected is final' => [ReturnStatus::Rejected, ReturnStatus::Approved, false];
        yield 'closed is final' => [ReturnStatus::Closed, ReturnStatus::Accepted, false];
    }

    #[DataProvider('productAvailabilityTransitionsProvider')]
    public function test_product_availability_graph(ProductAvailabilityStatus $from, ProductAvailabilityStatus $to, bool $expected): void
    {
        self::assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return iterable<string, array{ProductAvailabilityStatus, ProductAvailabilityStatus, bool}>
     */
    public static function productAvailabilityTransitionsProvider(): iterable
    {
        yield 'draft -> scheduled' => [ProductAvailabilityStatus::Draft, ProductAvailabilityStatus::Scheduled, true];
        yield 'draft -> available' => [ProductAvailabilityStatus::Draft, ProductAvailabilityStatus::Available, true];
        yield 'scheduled -> available' => [ProductAvailabilityStatus::Scheduled, ProductAvailabilityStatus::Available, true];
        yield 'scheduled -> draft' => [ProductAvailabilityStatus::Scheduled, ProductAvailabilityStatus::Draft, true];
        yield 'available -> temporarily_unavailable' => [ProductAvailabilityStatus::Available, ProductAvailabilityStatus::TemporarilyUnavailable, true];
        yield 'available -> discontinued' => [ProductAvailabilityStatus::Available, ProductAvailabilityStatus::Discontinued, true];
        yield 'temporarily_unavailable -> available' => [ProductAvailabilityStatus::TemporarilyUnavailable, ProductAvailabilityStatus::Available, true];
        yield 'discontinued -> archived' => [ProductAvailabilityStatus::Discontinued, ProductAvailabilityStatus::Archived, true];
        yield 'archived -> draft' => [ProductAvailabilityStatus::Archived, ProductAvailabilityStatus::Draft, true];
        yield 'discontinued is final' => [ProductAvailabilityStatus::Discontinued, ProductAvailabilityStatus::Available, false];
        yield 'available -> draft' => [ProductAvailabilityStatus::Available, ProductAvailabilityStatus::Draft, false];
    }

    public function test_is_final_reports_terminal_states_per_context(): void
    {
        self::assertFalse(OrderStatus::Completed->isFinal());
        self::assertTrue(OrderStatus::Cancelled->isFinal());
        self::assertFalse(OrderStatus::Pending->isFinal());

        self::assertTrue(PaymentStatus::Refunded->isFinal());
        self::assertFalse(PaymentStatus::Pending->isFinal());

        self::assertTrue(FulfillmentStatus::Fulfilled->isFinal());
        self::assertTrue(FulfillmentStatus::Cancelled->isFinal());
        self::assertFalse(FulfillmentStatus::Scheduled->isFinal());

        self::assertTrue(ShipmentStatus::Delivered->isFinal());
        self::assertFalse(ShipmentStatus::InTransit->isFinal());

        self::assertTrue(ReturnStatus::Refunded->isFinal());
        self::assertTrue(ReturnStatus::Closed->isFinal());
        self::assertFalse(ReturnStatus::Requested->isFinal());

        self::assertFalse(ProductAvailabilityStatus::Discontinued->isFinal());
        self::assertFalse(ProductAvailabilityStatus::Available->isFinal());
    }

    public function test_is_final_implies_no_outgoing_transitions(): void
    {
        foreach ([OrderStatus::class, PaymentStatus::class, FulfillmentStatus::class,
            ShipmentStatus::class, ReturnStatus::class, ProductAvailabilityStatus::class] as $enum) {
            foreach ($enum::cases() as $from) {
                if (! $from->isFinal()) {
                    continue;
                }

                foreach ($enum::cases() as $to) {
                    self::assertFalse(
                        $from->canTransitionTo($to),
                        sprintf('%s::%s is final but allows -> %s', $enum, $from->name, $to->name),
                    );
                }
            }
        }
    }
}
