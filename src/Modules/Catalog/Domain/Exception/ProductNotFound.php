<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Domain\Exception;

use Yeod\Modules\Catalog\Domain\ValueObject\ProductId;
use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a product cannot be found by identity or SKU.
 */
final class ProductNotFound extends DomainException
{
    /**
     * @param  string  $criteria  Criteria used for the lookup, included in the context.
     */
    private function __construct(private readonly string $criteria, string $message)
    {
        parent::__construct($message);
    }

    /**
     * Build the exception for a lookup by identity.
     *
     * @param  ProductId  $id  Identity that produced no result.
     */
    public static function withId(ProductId $id): self
    {
        return new self($id->value, sprintf('Product [%s] was not found.', $id->value));
    }

    /**
     * Build the exception for a lookup by SKU.
     *
     * @param  Sku  $sku  SKU that produced no result.
     */
    public static function withSku(Sku $sku): self
    {
        return new self($sku->value, sprintf('Product with SKU [%s] was not found.', $sku->value));
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode(): string
    {
        return 'catalog.product_not_found';
    }

    /**
     * {@inheritDoc}
     */
    public function context(): array
    {
        return ['criteria' => $this->criteria];
    }
}
