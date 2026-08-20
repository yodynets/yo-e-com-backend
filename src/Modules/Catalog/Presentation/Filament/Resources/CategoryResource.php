<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources;

use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Pages\CreateCategory;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Pages\EditCategory;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Pages\ListCategories;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Schemas\CategoryForm;
use Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Tables\CategoriesTable;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    //protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $recordTitleAttribute = 'Category';
    //protected static ?string $navigationLabel = 'Category';

    //    protected static ?int $navigationSort = 1;
    //
    //    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit'   => EditCategory::route('/{record}/edit'),
        ];
    }
}
