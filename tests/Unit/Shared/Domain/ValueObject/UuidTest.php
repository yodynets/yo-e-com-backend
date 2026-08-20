<?php

declare(strict_types=1);

namespace Yeod\Tests\Unit\Shared\Domain\ValueObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Shared\Domain\Exception\InvalidValueObject;
use Yeod\Shared\Domain\ValueObject\Uuid;

#[CoversClass(Uuid::class)]
final class UuidTest extends TestCase
{
    #[Test]
    public function it_generates_a_version_4_identifier(): void
    {
        $id = ProductId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value,
        );
    }

    #[Test]
    public function generated_identifiers_are_unique(): void
    {
        self::assertNotSame(ProductId::generate()->value, ProductId::generate()->value);
    }

    #[Test]
    public function it_keeps_the_concrete_type_when_generating(): void
    {
        self::assertInstanceOf(ProductId::class, ProductId::generate());
    }

    #[Test]
    public function it_restores_from_a_string_case_insensitively(): void
    {
        $id = ProductId::generate();

        self::assertTrue(ProductId::fromString(strtoupper($id->value))->equals($id));
    }

    #[Test]
    public function it_rejects_malformed_identifiers(): void
    {
        $this->expectException(InvalidValueObject::class);

        ProductId::fromString('not-a-uuid');
    }
}
