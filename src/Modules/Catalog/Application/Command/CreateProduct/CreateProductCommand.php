<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Application\Command\CreateProduct;

use Yeod\Shared\Application\Bus\Command;

/**
 * Add a new product to the catalogue.
 */
final readonly class CreateProductCommand implements Command
{
    /**
     * @param  string  $sku  Business identifier, must be unique.
     * @param  string  $name  Display name.
     * @param  string  $price  Price as a decimal string, for example `"1299.00"`.
     * @param  string  $currency  ISO 4217 currency code, for example `UAH`.
     */
    public function __construct(
        public string $sku,
        public string $name,
        public string $price,
        public string $currency,
    ) {}

    /**
     * Build the command from a validated request payload.
     *
     * @param  array{sku: string, name: string, price: string|float|int, currency: string}  $payload  Validated input.
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            sku     : $payload['sku'],
            name    : $payload['name'],
            price   : (string)$payload['price'],
            currency: $payload['currency'],
        );
    }
}
