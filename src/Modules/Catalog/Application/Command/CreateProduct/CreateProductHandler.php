<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Application\Command\CreateProduct;

use Yeod\Modules\Catalog\Domain\Entity\Product;
use Yeod\Modules\Catalog\Domain\Exception\SkuAlreadyExists;
use Yeod\Modules\Catalog\Domain\Repository\ProductRepository;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Shared\Application\Bus\Command;
use Yeod\Shared\Application\Bus\CommandHandler;
use Yeod\Shared\Domain\Clock\Clock;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

/**
 * Handles {@see CreateProductCommand}.
 *
 * @implements CommandHandler<CreateProductCommand>
 */
final readonly class CreateProductHandler implements CommandHandler
{
    /**
     * @param  ProductRepository  $products  Write side port of the catalog.
     * @param  Clock  $clock  Source of the current time.
     */
    public function __construct(
        private ProductRepository $products,
        private Clock $clock,
    ) {}

    /**
     * Create the product and return its identity.
     *
     * @param  CreateProductCommand  $command  Command to execute.
     * @return string Identity of the created product.
     *
     * @throws SkuAlreadyExists When the SKU is already taken.
     */
    public function handle(Command $command): string
    {
        $sku = Sku::fromString($command->sku);

        if ($this->products->existsWithSku($sku)) {
            throw new SkuAlreadyExists($sku);
        }

        $product = Product::create(
            id: $this->products->nextIdentity(),
            sku: $sku,
            name: $command->name,
            price: Money::fromDecimalString($command->price, Currency::fromCode($command->currency)),
            now: $this->clock->now(),
        );

        $this->products->save($product);

        return $product->id()->value;
    }
}
