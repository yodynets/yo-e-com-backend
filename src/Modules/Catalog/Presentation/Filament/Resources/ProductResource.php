<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Yeod\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\ProductModel;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages\CreateProduct;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages\EditProduct;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\ProductResource\Pages\ListProducts;
use Yeod\Shared\Domain\ValueObject\Currency;

/**
 * Filament resource for catalogue products.
 *
 * Filament is a Presentation detail. The resource is only allowed to describe the
 * UI: every write goes through a command on the page classes, never through
 * `$record->save()`. The Eloquent model is referenced solely because Filament needs
 * a query builder for its table; this is the single documented concession to the
 * layering rules.
 */
final class ProductResource extends Resource
{
    /**
     * Eloquent model backing the resource's table and record resolution.
     *
     * @var class-string<ProductModel>|null
     */
    protected static ?string $model = ProductModel::class;

    /**
     * Navigation icon shown in the panel sidebar.
     */
    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-cube';

    /**
     * Attribute used when Filament renders a record title.
     */
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Describe the create and edit form.
     *
     * @param  Schema  $schema  Schema provided by Filament.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                     ->label(__('catalog::product.sku'))
                     ->required()
                     ->maxLength(64)
                     ->disabledOn('edit')
                     ->helperText(__('catalog::product.sku_is_immutable')),

            TextInput::make('name')
                     ->label(__('catalog::product.name'))
                     ->required()
                     ->maxLength(255),

            TextInput::make('price')
                     ->label(__('catalog::product.price'))
                     ->required()
                     ->numeric()
                     ->minValue(0)
                     ->step(0.01),

            Select::make('currency')
                  ->label(__('catalog::product.currency'))
                  ->required()
                  ->options(
                      array_combine(
                          array_column(Currency::cases(), 'value'),
                          array_column(Currency::cases(), 'value'),
                      )
                  )
                  ->default((string)config('catalog.default_currency')),

            Toggle::make('active')
                  ->label(__('catalog::product.active'))
                  ->default(true),
        ]);
    }

    /**
     * Describe the list table.
     *
     * @param  Table  $table  Table provided by Filament.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label(__('catalog::product.sku'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('catalog::product.name'))->searchable()->sortable(),
                TextColumn::make('price_minor_amount')
                          ->label(__('catalog::product.price'))
                          ->sortable()
                          ->formatStateUsing(static fn(int $state): string => number_format($state / 100, 2, '.', ' ')),
                TextColumn::make('currency')->label(__('catalog::product.currency')),
                IconColumn::make('active')->label(__('catalog::product.active'))->boolean(),
                TextColumn::make('created_at')->label(__('catalog::product.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Pages exposed by the resource.
     *
     * @return array<string, mixed>
     */
    public static function getPages(): array
    {
        return [
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * Navigation group taken from the module configuration.
     */
    public static function getNavigationGroup(): ?string
    {
        return (string)config('catalog.navigation.group', 'Catalog');
    }

    /**
     * Navigation sort order taken from the module configuration.
     */
    public static function getNavigationSort(): ?int
    {
        return (int)config('catalog.navigation.sort', 10);
    }
}
