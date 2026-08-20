<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Application\Command\ChangeProductPrice;

use Yeod\Modules\Catalog\Domain\Exception\ProductNotFound;
use Yeod\Modules\Catalog\Domain\Repository\ProductRepository;
use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Shared\Application\Bus\Command;
use Yeod\Shared\Application\Bus\CommandHandler;
use Yeod\Shared\Domain\Clock\Clock;
use Yeod\Shared\Domain\ValueObject\Currency;
use Yeod\Shared\Domain\ValueObject\Money;

/**
 * Handles {@see ChangeProductPriceCommand}.
 *
 * @implements CommandHandler<ChangeProductPriceCommand>
 */
final readonly class ChangeProductPriceHandler implements CommandHandler
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
     * Reprice the product.
     *
     * @param  ChangeProductPriceCommand  $command  Command to execute.
     *
     * @throws ProductNotFound When the product does not exist.
     */
    public function handle(Command $command): null
    {
        $product = $this->products->get(ProductId::fromString($command->productId));

        $product->changePrice(
            Money::fromDecimalString($command->price, Currency::fromCode($command->currency)),
            $this->clock->now(),
        );

        $this->products->save($product);

        return null;
    }
}
