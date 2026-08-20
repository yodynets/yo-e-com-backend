<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Domain\ValueObject;

use Yeod\Shared\Domain\ValueObject\Uuid;

/**
 * Identity of a catalogue product.
 *
 * A dedicated type (instead of a bare string or an auto increment id) makes it
 * impossible to pass an order id where a product id is expected.
 */
final readonly class ProductId extends Uuid {}
