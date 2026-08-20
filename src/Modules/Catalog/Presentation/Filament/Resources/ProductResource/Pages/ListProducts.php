<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource;

/**
 * Product list page.
 */
final class ListProducts extends ListRecords
{
    /**
     * Resource this page belongs to.
     *
     * @var class-string<ProductResource>
     */
    protected static string $resource = ProductResource::class;

    /**
     * Header actions of the page.
     *
     * @return list<CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
