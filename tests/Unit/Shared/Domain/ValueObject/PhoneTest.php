<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Shared\Domain\Exception\InvalidValueObject;
use Yeod\Shared\Domain\ValueObject\Phone;

#[CoversClass(Phone::class)]
final class PhoneTest extends TestCase
{
    /**
     * @param  string  $input  Raw phone input.
     * @param  string  $expected  Expected E.164 result.
     */
    #[Test]
    #[DataProvider('normalisableNumbers')]
    public function it_normalises_to_e164(string $input, string $expected): void
    {
        self::assertSame($expected, Phone::fromString($input)->value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalisableNumbers(): iterable
    {
        yield 'already e164' => ['+380671234567', '+380671234567'];
        yield 'formatted' => ['(067) 123-45-67', '+380671234567'];
        yield 'double zero prefix' => ['00380671234567', '+380671234567'];
        yield 'international' => ['+1 202 555 0143', '+12025550143'];
    }

    /**
     * @param  string  $invalid  Malformed phone input.
     */
    #[Test]
    #[DataProvider('invalidNumbers')]
    public function it_rejects_invalid_numbers(string $invalid): void
    {
        $this->expectException(InvalidValueObject::class);

        Phone::fromString($invalid);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNumbers(): iterable
    {
        yield 'letters only' => ['no digits here'];
        yield 'too short' => ['12345'];
        yield 'too long' => ['1234567890123456'];
    }

    #[Test]
    public function it_exposes_digits_and_compares_by_value(): void
    {
        $phone = Phone::fromString('0671234567');

        self::assertSame('380671234567', $phone->digits());
        self::assertTrue($phone->equals(Phone::fromString('+380671234567')));
        self::assertNull(Phone::tryFromString('abc'));
    }
}
