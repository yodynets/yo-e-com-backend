<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Domain\Exception;

use Yeod\Modules\Catalog\Domain\ValueObject\Sku;
use Yeod\Shared\Domain\Exception\DomainException;

/**
 * Thrown when a product is created with a SKU that is already taken.
 *
 * The uniqueness rule is enforced twice on purpose: here for a clean business
 * error, and by a unique index in the database as the ultimate guard.
 */
final class SkuAlreadyExists extends DomainException
{
    /**
     * @param  Sku  $sku  SKU that is already in use.
     */
    public function __construct(private readonly Sku $sku)
    {
        parent::__construct(sprintf('A product with SKU [%s] already exists.', $sku->value));
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode(): string
    {
        return 'catalog.sku_already_exists';
    }

    /**
     * {@inheritDoc}
     */
    public function context(): array
    {
        return ['sku' => $this->sku->value];
    }
}
