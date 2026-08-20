<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Shared\Domain\Exception\InvalidValueObject;
use Yeod\Shared\Domain\ValueObject\Email;

#[CoversClass(Email::class)]
final class EmailTest extends TestCase
{
    #[Test]
    public function it_normalises_case_and_whitespace(): void
    {
        $email = Email::fromString('  John.Doe@Example.COM ');

        self::assertSame('john.doe@example.com', $email->value);
        self::assertSame('john.doe', $email->localPart());
        self::assertSame('example.com', $email->domain());
        self::assertSame('john.doe@example.com', (string) $email);
    }

    /**
     * @param  string  $invalid  Malformed address.
     */
    #[Test]
    #[DataProvider('invalidAddresses')]
    public function it_rejects_invalid_addresses(string $invalid): void
    {
        $this->expectException(InvalidValueObject::class);

        Email::fromString($invalid);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidAddresses(): iterable
    {
        yield 'empty' => ['   '];
        yield 'no at sign' => ['john.doe'];
        yield 'no domain' => ['john@'];
        yield 'spaces inside' => ['jo hn@example.com'];
        yield 'too long' => [str_repeat('a', 250).'@example.com'];
    }

    #[Test]
    public function it_can_fail_softly_for_legacy_imports(): void
    {
        self::assertNull(Email::tryFromString('not-an-email'));
        self::assertNull(Email::tryFromString(null));
        self::assertNull(Email::tryFromString(''));
        self::assertInstanceOf(Email::class, Email::tryFromString('ok@example.com'));
    }

    #[Test]
    public function it_compares_by_value(): void
    {
        self::assertTrue(Email::fromString('a@b.com')->equals(Email::fromString('A@B.com')));
        self::assertFalse(Email::fromString('a@b.com')->equals(Email::fromString('c@b.com')));
        self::assertSame('a@b.com', Email::fromString('a@b.com')->toPrimitive());
    }
}
