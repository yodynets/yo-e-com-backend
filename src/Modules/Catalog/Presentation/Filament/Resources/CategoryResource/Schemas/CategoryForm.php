<?php

declare(strict_types = 1);

namespace Yeod\Modules\Catalog\Presentation\Filament\Resources\CategoryResource\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Information')
                       ->schema([
                           TextInput::make('ref')->readOnly()->disabled(),
                           TextInput::make('code')->numeric()->readOnly()->disabled(),
                           Select::make('parent_id')
                                 ->label('Parent Category')
                                 ->placeholder('Select parent category (optional)')
                                 ->options(
                                     Category::whereNull('parent_id')->pluck('name', 'id'),
                                 )
                                 ->nullable()
                                 ->searchable()->preload()
                                 ->columnSpanFull(),
                           TextInput::make('name')
                                    ->label('Category Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set): void {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                           TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                    ->helperText('The slug is auto generated from the category name'),
                           Toggle::make('is_active')
                                 ->label('Active Status')
                                 ->default(false)
                                 ->onColor('success')
                                 ->offColor('danger')
                                 ->columnSpanFull(),

                           //                        Select::make('parent_id')
                           //                            ->relationship('parent', 'name'),

                           //                        Textarea::make('description')
                           //                            ->columnSpanFull(),
                           //                        Textarea::make('comments')
                           //                            ->columnSpanFull(),
                           //                        Toggle::make('is_top')
                           //                            ->required(),
                           //                        TextInput::make('menu_columns_count')
                           //                            ->required()
                           //                            ->numeric()
                           //                            ->default(1),
                           //                        TextInput::make('sort_order')
                           //                            ->required()
                           //                            ->numeric()
                           //                            ->default(0),
                       ])->columns(2),
            ]);
    }
}
