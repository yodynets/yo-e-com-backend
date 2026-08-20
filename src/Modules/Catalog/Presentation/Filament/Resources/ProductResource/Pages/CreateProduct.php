<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Yeod\Modules\Catalog\Application\Command\CreateProduct\CreateProductCommand;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource;
use Yeod\Shared\Application\Bus\CommandBus;

/**
 * Product creation page.
 *
 * This is the reference example of the Filament rule: the page dispatches a command
 * and only then reads the record back for the UI. It never writes through Eloquent.
 */
final class CreateProduct extends CreateRecord
{
    /**
     * Resource this page belongs to.
     *
     * @var class-string<ProductResource>
     */
    protected static string $resource = ProductResource::class;

    /**
     * Persist the form data through the Application layer.
     *
     * @param  array<string, mixed>  $data  Validated form state.
     *
     * @return Model Freshly created record, required by Filament for redirects.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $productId = app(CommandBus::class)->dispatch(
            new CreateProductCommand(
                sku     : (string)$data['sku'],
                name    : (string)$data['name'],
                price   : (string)$data['price'],
                currency: (string)$data['currency'],
            )
        );

        return $this->getModel()::query()->findOrFail($productId);
    }
}
