<?php

declare(strict_types=1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Yeod\Modules\Catalog\Application\Command\ChangeProductPrice\ChangeProductPriceCommand;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource;
use Yeod\Shared\Application\Bus\CommandBus;

/**
 * Product edit page.
 *
 * Every mutation is expressed as a command, one per business intent. Adding a
 * "rename product" feature means adding a command, not touching the model.
 */
final class EditProduct extends EditRecord
{
    /**
     * Resource this page belongs to.
     *
     * @var class-string<ProductResource>
     */
    protected static string $resource = ProductResource::class;

    /**
     * Fill the form with a decimal price instead of minor units.
     *
     * @param  array<string, mixed>  $data  Raw record attributes.
     * @return array<string, mixed> Form state.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['price'] = number_format(((int) $data['price_minor_amount']) / 100, 2, '.', '');

        return $data;
    }

    /**
     * Apply the form changes through the Application layer.
     *
     * @param  Model  $record  Record being edited.
     * @param  array<string, mixed>  $data  Validated form state.
     * @return Model Refreshed record.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(CommandBus::class)->dispatch(new ChangeProductPriceCommand(
            productId: (string) $record->getKey(),
            price: (string) $data['price'],
            currency: (string) $data['currency'],
        ));

        return $record->refresh();
    }
}
