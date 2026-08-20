<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Shared\Domain\Event;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Shared\Domain\Clock\FrozenClock;
use Yeod\Shared\Domain\Event\RecordsDomainEvents;
use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\Event\ProductWasCreated;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

#[CoversTrait(RecordsDomainEvents::class)]
final class AggregateRootTest extends TestCase
{
    #[Test]
    public function it_buffers_events_until_they_are_released(): void
    {
        $product = $this->product();

        self::assertTrue($product->hasRecordedEvents());

        $events = $product->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(ProductWasCreated::class, $events[0]);
        self::assertFalse($product->hasRecordedEvents());
        self::assertSame([], $product->releaseEvents());
    }

    #[Test]
    public function released_events_carry_a_primitive_payload(): void
    {
        $event = $this->product()->releaseEvents()[0];

        self::assertSame('catalog.product.created', $event->eventName());
        self::assertSame('2026-01-01T00:00:00+00:00', $event->occurredAt()->format(DATE_ATOM));
        self::assertSame('TSH-001', $event->payload()['sku']);
    }

    /**
     * Build a product recorded at a fixed moment in time.
     */
    private function product(): Product
    {
        return Product::create(
            id: ProductId::generate(),
            sku: Sku::fromString('tsh-001'),
            name: 'T-shirt',
            price: Money::fromMinor(49900, Currency::UAH),
            now: FrozenClock::at('2026-01-01T00:00:00')->now(),
        );
    }
}
