<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Modules\Catalog\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\Event\ProductPriceWasChanged;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Shared\Domain\Clock\FrozenClock;
use Yeod\Shared\Domain\Exception\CurrencyMismatch;
use Yeod\Shared\Domain\Exception\InvalidValueObject;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

#[CoversClass(Product::class)]
#[CoversClass(Sku::class)]
final class ProductTest extends TestCase
{
    private FrozenClock $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = FrozenClock::at('2026-02-01T12:00:00');
    }

    #[Test]
    public function it_is_created_active_with_a_normalised_sku(): void
    {
        $product = $this->product();

        self::assertSame('TSH-001', $product->sku()->value);
        self::assertSame('T-shirt', $product->name());
        self::assertTrue($product->isActive());
        self::assertTrue($product->price()->equals(Money::fromMinor(49900, Currency::UAH)));
    }

    #[Test]
    public function it_rejects_an_empty_name(): void
    {
        $this->expectException(InvalidValueObject::class);

        $this->product(name: '   ');
    }

    #[Test]
    public function it_rejects_a_negative_price(): void
    {
        $this->expectException(InvalidValueObject::class);

        $this->product(price: Money::fromMinor(-1, Currency::UAH));
    }

    #[Test]
    public function it_records_an_event_when_the_price_changes(): void
    {
        $product = $this->product();
        $product->releaseEvents();

        $product->changePrice(Money::fromMinor(59900, Currency::UAH), $this->clock->now());
        $events = $product->releaseEvents();

        self::assertCount(1, $events);
        $event = $events[0];
        self::assertInstanceOf(ProductPriceWasChanged::class, $event);
        self::assertSame(49900, $event->oldMinorAmount);
        self::assertSame(59900, $event->newMinorAmount);
        self::assertSame($product->id()->value, $event->aggregateId());
    }

    #[Test]
    public function repricing_to_the_same_amount_records_nothing(): void
    {
        $product = $this->product();
        $product->releaseEvents();

        $product->changePrice(Money::fromMinor(49900, Currency::UAH), $this->clock->now());

        self::assertFalse($product->hasRecordedEvents());
    }

    #[Test]
    public function it_refuses_to_change_the_price_currency(): void
    {
        $this->expectException(CurrencyMismatch::class);

        $this->product()->changePrice(Money::fromMinor(1000, Currency::USD), $this->clock->now());
    }

    #[Test]
    public function it_can_be_renamed_and_deactivated(): void
    {
        $product = $this->product();

        $product->rename('  Premium T-shirt ');
        $product->deactivate();

        self::assertSame('Premium T-shirt', $product->name());
        self::assertFalse($product->isActive());

        $product->activate();

        self::assertTrue($product->isActive());
    }

    #[Test]
    public function it_is_restored_without_recording_events(): void
    {
        $product = Product::restore(
            id: ProductId::generate(),
            sku: Sku::fromString('TSH-001'),
            name: 'T-shirt',
            price: Money::fromMinor(49900, Currency::UAH),
            active: true,
            createdAt: $this->clock->now(),
        );

        self::assertFalse($product->hasRecordedEvents());
    }

    /**
     * Build a product for the test at hand.
     *
     * @param  string  $name  Product name.
     * @param  Money|null  $price  Product price, defaults to 499.00 UAH.
     */
    private function product(string $name = 'T-shirt', ?Money $price = null): Product
    {
        return Product::create(
            id: ProductId::generate(),
            sku: Sku::fromString('tsh-001'),
            name: $name,
            price: $price ?? Money::fromMinor(49900, Currency::UAH),
            now: $this->clock->now(),
        );
    }
}
