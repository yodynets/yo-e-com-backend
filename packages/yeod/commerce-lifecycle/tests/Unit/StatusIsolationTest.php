<?php

declare(strict_types=1);

namespace Yeod\CommerceLifecycle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeod\CommerceLifecycle\Domain\Catalog\ProductAvailabilityStatus;
use Yeod\CommerceLifecycle\Domain\Fulfillment\Fulfillment;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentLine;
use Yeod\CommerceLifecycle\Domain\Fulfillment\FulfillmentStatus;
use Yeod\CommerceLifecycle\Domain\Order\OrderStatus;
use Yeod\CommerceLifecycle\Domain\Payment\PaymentStatus;
use Yeod\CommerceLifecycle\Domain\ReturnFlow\ReturnStatus;
use Yeod\CommerceLifecycle\Domain\Shipment\ShipmentStatus;
use Yeod\CommerceLifecycle\Exceptions\CommerceLifecycleException;
use Yeod\CommerceLifecycle\Exceptions\InvalidTransitionException;

/**
 * Locks in the "statuses never leak across contexts" guarantee.
 *
 * Because every enum exposes `canTransitionTo(self $target)`, passing a status
 * from another axis is a hard TypeError — the language refuses the mix instead
 * of silently returning false. These tests document and protect that behaviour,
 * and verify that forbidden transitions raise the package domain exception.
 */
final class StatusIsolationTest extends TestCase
{
    /**
     * Each status axis must reject a foreign-status argument with a TypeError.
     * This proves the enums are isolated at the language level, not by convention.
     */
    public function test_fulfillment_status_rejects_payment_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => FulfillmentStatus::Unfulfilled->canTransitionTo($target),
            PaymentStatus::Captured,
        );
    }

    public function test_order_status_rejects_shipment_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => OrderStatus::AwaitingPayment->canTransitionTo($target),
            ShipmentStatus::Delivered,
        );
    }

    public function test_payment_status_rejects_return_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => PaymentStatus::Captured->canTransitionTo($target),
            ReturnStatus::Refunded,
        );
    }

    public function test_shipment_status_rejects_catalog_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => ShipmentStatus::InTransit->canTransitionTo($target),
            ProductAvailabilityStatus::Available,
        );
    }

    public function test_return_status_rejects_order_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => ReturnStatus::Inspecting->canTransitionTo($target),
            OrderStatus::Completed,
        );
    }

    public function test_catalog_status_rejects_fulfillment_status(): void
    {
        $this->expectForeignTypeError(
            static fn (mixed $target): bool => ProductAvailabilityStatus::Available->canTransitionTo($target),
            FulfillmentStatus::Fulfilled,
        );
    }

    /**
     * Assert that passing a foreign status enum into a typed transition raises a
     * TypeError at runtime (the isolation guarantee), routed through a `mixed`
     * parameter so the intentional mismatch is not flagged as a static error.
     *
     * @param  callable(mixed): bool  $probe
     */
    private function expectForeignTypeError(callable $probe, mixed $target): void
    {
        $this->expectException(\TypeError::class);
        $probe($target);
    }

    public function test_forbidden_transition_throws_package_domain_exception(): void
    {
        $fulfillment = Fulfillment::create('ful-1', 'ord-1', [new FulfillmentLine('line-1', 'sku-1', 1)]);
        $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
        $fulfillment->changeStatus(FulfillmentStatus::Fulfilled);

        try {
            $fulfillment->changeStatus(FulfillmentStatus::Unfulfilled);
            self::fail('Expected InvalidTransitionException was not thrown.');
        } catch (CommerceLifecycleException $e) {
            self::assertInstanceOf(InvalidTransitionException::class, $e);
        }
    }

    public function test_invalid_transition_exception_carries_from_and_to(): void
    {
        $exception = InvalidTransitionException::from(FulfillmentStatus::Fulfilled, FulfillmentStatus::Unfulfilled);

        self::assertStringContainsString('Fulfilled', $exception->getMessage());
        self::assertStringContainsString('Unfulfilled', $exception->getMessage());
        self::assertInstanceOf(CommerceLifecycleException::class, $exception);
    }
}
