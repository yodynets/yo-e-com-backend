<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Shared\Domain\Exception\CurrencyMismatch;
use Yeod\Shared\Domain\Exception\InvalidValueObject;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

#[CoversClass(Money::class)]
#[CoversClass(Currency::class)]
final class MoneyTest extends TestCase
{
    #[Test]
    public function it_builds_from_minor_units(): void
    {
        $money = Money::fromMinor(1999, Currency::UAH);

        self::assertSame(1999, $money->minorAmount);
        self::assertSame('19.99', $money->toDecimalString());
        self::assertSame('19.99 UAH', (string) $money);
    }

    /**
     * @param  string  $input  Raw decimal input.
     * @param  int  $expected  Expected amount in minor units.
     */
    #[Test]
    #[DataProvider('decimalStrings')]
    public function it_builds_from_decimal_strings(string $input, int $expected): void
    {
        self::assertSame($expected, Money::fromDecimalString($input, Currency::UAH)->minorAmount);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function decimalStrings(): iterable
    {
        yield 'integer' => ['1299', 129900];
        yield 'two decimals' => ['1299.50', 129950];
        yield 'one decimal' => ['0.5', 50];
        yield 'negative' => ['-12.34', -1234];
        yield 'comma separator' => ['12,34', 1234];
        yield 'thousand spaces' => ['1 000.00', 100000];
    }

    #[Test]
    public function it_rejects_malformed_amounts(): void
    {
        $this->expectException(InvalidValueObject::class);

        Money::fromDecimalString('12.3.4', Currency::UAH);
    }

    #[Test]
    public function it_rejects_more_decimals_than_the_currency_allows(): void
    {
        $this->expectException(InvalidValueObject::class);

        Money::fromDecimalString('10.123', Currency::UAH);
    }

    #[Test]
    public function it_adds_and_subtracts(): void
    {
        $ten = Money::fromMinor(1000, Currency::UAH);
        $three = Money::fromMinor(300, Currency::UAH);

        self::assertSame(1300, $ten->add($three)->minorAmount);
        self::assertSame(700, $ten->subtract($three)->minorAmount);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $ten = Money::fromMinor(1000, Currency::UAH);
        $ten->add(Money::fromMinor(500, Currency::UAH));

        self::assertSame(1000, $ten->minorAmount);
    }

    #[Test]
    public function it_refuses_to_mix_currencies(): void
    {
        $this->expectException(CurrencyMismatch::class);

        Money::fromMinor(100, Currency::UAH)->add(Money::fromMinor(100, Currency::USD));
    }

    #[Test]
    public function it_multiplies_by_a_quantity(): void
    {
        self::assertSame(2997, Money::fromMinor(999, Currency::UAH)->multiplyBy(3)->minorAmount);
    }

    #[Test]
    public function it_rejects_a_negative_quantity(): void
    {
        $this->expectException(InvalidValueObject::class);

        Money::fromMinor(999, Currency::UAH)->multiplyBy(-1);
    }

    #[Test]
    public function it_applies_a_percentage_with_half_up_rounding(): void
    {
        self::assertSame(200, Money::fromMinor(1000, Currency::UAH)->percentage(20.0)->minorAmount);
        self::assertSame(2, Money::fromMinor(15, Currency::UAH)->percentage(10.0)->minorAmount);
    }

    #[Test]
    public function it_compares_amounts(): void
    {
        $small = Money::fromMinor(100, Currency::EUR);
        $big = Money::fromMinor(500, Currency::EUR);

        self::assertTrue($big->greaterThan($small));
        self::assertTrue($small->lessThan($big));
        self::assertSame(0, $small->compareTo(Money::fromMinor(100, Currency::EUR)));
    }

    #[Test]
    public function it_reports_its_sign(): void
    {
        self::assertTrue(Money::zero(Currency::UAH)->isZero());
        self::assertTrue(Money::fromMinor(-1, Currency::UAH)->isNegative());
        self::assertTrue(Money::fromMinor(1, Currency::UAH)->isPositive());
        self::assertSame('-0.01', Money::fromMinor(-1, Currency::UAH)->toDecimalString());
    }

    #[Test]
    public function it_negates_an_amount(): void
    {
        self::assertSame(-500, Money::fromMinor(500, Currency::UAH)->negate()->minorAmount);
    }

    #[Test]
    public function it_compares_by_value(): void
    {
        self::assertTrue(Money::fromMinor(100, Currency::UAH)->equals(Money::fromMinor(100, Currency::UAH)));
        self::assertFalse(Money::fromMinor(100, Currency::UAH)->equals(Money::fromMinor(100, Currency::USD)));
    }

    #[Test]
    public function it_resolves_currencies_case_insensitively(): void
    {
        self::assertSame(Currency::USD, Currency::fromCode('usd'));
        self::assertSame(100, Currency::UAH->minorUnitFactor());
    }

    #[Test]
    public function it_rejects_unsupported_currencies(): void
    {
        $this->expectException(InvalidValueObject::class);

        Currency::fromCode('XYZ');
    }
}
