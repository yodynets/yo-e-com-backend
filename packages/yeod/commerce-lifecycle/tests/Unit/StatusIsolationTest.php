<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
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
     *
     * @return iterable<string, array{callable(): void}>
     */
    public static function foreignContextProbeProvider(): iterable
    {
        yield 'fulfillment vs payment' => [
            static fn (): mixed => FulfillmentStatus::Unfulfilled->canTransitionTo(PaymentStatus::Captured),
        ];
        yield 'order vs shipment' => [
            static fn (): mixed => OrderStatus::AwaitingPayment->canTransitionTo(ShipmentStatus::Delivered),
        ];
        yield 'payment vs return' => [
            static fn (): mixed => PaymentStatus::Captured->canTransitionTo(ReturnStatus::Refunded),
        ];
        yield 'shipment vs catalog' => [
            static fn (): mixed => ShipmentStatus::InTransit->canTransitionTo(ProductAvailabilityStatus::Available),
        ];
        yield 'return vs order' => [
            static fn (): mixed => ReturnStatus::Inspecting->canTransitionTo(OrderStatus::Completed),
        ];
        yield 'catalog vs fulfillment' => [
            static fn (): mixed => ProductAvailabilityStatus::Available->canTransitionTo(FulfillmentStatus::Fulfilled),
        ];
    }

    #[DataProvider('foreignContextProbeProvider')]
    public function test_foreign_status_enum_is_a_type_error(callable $probe): void
    {
        $this->expectException(\TypeError::class);
        $probe();
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